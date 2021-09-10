<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\ORM\TempTableManipulatorInterface;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;

/**
 * Doctrine repository for Oro\Bundle\PricingBundle\Entity\CombinedProductPrice entity
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CombinedProductPriceRepository extends BaseProductPriceRepository
{
    const BATCH_SIZE = 10000;

    /**
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @param bool $mergeAllowed
     * @param array|Product[] $products
     */
    public function copyPricesByPriceList(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        bool $mergeAllowed,
        array $products = []
    ) {
        $this->doInsertByProducts(
            $insertFromSelectQueryExecutor,
            $priceList,
            $products,
            $this->getPricesQb($combinedPriceList, $mergeAllowed, $priceList)
        );
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function insertPricesByPriceListWithTempTable(
        TempTableManipulatorInterface $tempTableManipulator,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        bool $mergeAllowed,
        array $products
    ) {
        // Copy prices for products that are not in the CPL yet to temp table (faster insert)
        $notInListQb = $this->getPricesQb($combinedPriceList, $mergeAllowed, $priceList);
        $this->addPresentProductsRestriction($notInListQb, $combinedPriceList);

        $tempTableName = $tempTableManipulator->getTempTableNameForEntity(
            CombinedProductPrice::class,
            $combinedPriceList->getId()
        );

        // Source - PL, restricted by - CPL, target - TMP
        $this->doInsertByProductsUsingTempTable(
            $tempTableManipulator,
            $combinedPriceList,
            $priceList,
            $products,
            $notInListQb,
            $tempTableName,
            false
        );

        // For merge allowed add prices not blocked by merge:false for qty/units that are not present yet
        // Skip prices moved to temp table
        $qb = $this->getPricesQb($combinedPriceList, $mergeAllowed, $priceList);
        $this->addProductsBlockedByMergeFlagRestriction($qb, $combinedPriceList);
        $this->addPresentPricesRestriction($qb, $combinedPriceList);

        // Apply restriction by temp table
        $tempTableSubQb = $this->_em->createQueryBuilder();
        $tempTableSubQb->select('cpp_tmp.id')
            ->from(CombinedProductPrice::class, 'cpp_tmp')
            ->where(
                $tempTableSubQb->expr()->eq('pp.product', 'cpp_tmp.product')
            );
        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists($tempTableSubQb->getDQL())
            )
        );

        // Source - PL, restricted by - TMP, target - CPL
        $this->doInsertByProductsUsingTempTable(
            $tempTableManipulator,
            $combinedPriceList,
            $priceList,
            $products,
            $qb,
            $tempTableManipulator->getTableNameForEntity(CombinedProductPrice::class),
            true,
            ['cpp_tmp' => $tempTableName]
        );

        // Move prices from temp to persistent CPL table
        $tempTableManipulator->moveDataFromTemplateTableToEntityTable(
            CombinedProductPrice::class,
            $combinedPriceList->getId(),
            [
                'product',
                'unit',
                'priceList',
                'productSku',
                'quantity',
                'value',
                'currency',
                'mergeAllowed',
                'originPriceId',
                'id'
            ]
        );
    }

    /**
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @param boolean $mergeAllowed
     * @param array|Product[] $products
     */
    public function insertPricesByPriceList(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        $mergeAllowed,
        array $products = []
    ) {
        $qb = $this->getPricesQb($combinedPriceList, $mergeAllowed, $priceList);
        $this->addUniquePriceCondition($qb, $combinedPriceList, $mergeAllowed);

        $this->doInsertByProducts(
            $insertFromSelectQueryExecutor,
            $priceList,
            $products,
            $qb
        );
    }

    public function insertPricesByCombinedPriceList(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $sourceCpl
    ) {
        $qb = $this->getEntityManager()
            ->getRepository(CombinedProductPrice::class)
            ->createQueryBuilder('pp');

        $qb
            ->select(
                'IDENTITY(pp.product)',
                'IDENTITY(pp.unit)',
                (string)$qb->expr()->literal($combinedPriceList->getId()),
                'pp.productSku',
                'pp.quantity',
                'pp.value',
                'pp.currency',
                sprintf('CAST(%d as boolean)', 1),
                'pp.originPriceId',
                'UUID()'
            )
            ->where($qb->expr()->eq('pp.priceList', ':currentPriceList'))
            ->setParameter('currentPriceList', $sourceCpl);
        $this->addUniquePriceCondition($qb, $combinedPriceList, true);
        $this->insertByProductsRangeByCpl($insertFromSelectQueryExecutor, $sourceCpl, $qb);
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|Product[] $products
     */
    public function deleteCombinedPrices(CombinedPriceList $combinedPriceList, array $products = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'combinedPrice')
            ->where($qb->expr()->eq('combinedPrice.priceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList);

        if ($products) {
            $qb->andWhere($qb->expr()->in('combinedPrice.product', ':products'))
                ->setParameter('products', $products);
        }

        $qb->getQuery()->execute();
    }

    /**
     * @param CombinedPriceList $priceList
     * @param array $productIds
     * @param null|string $currency
     * @return CombinedProductPrice[]
     */
    public function getPricesForProductsByPriceList(CombinedPriceList $priceList, array $productIds, $currency = null)
    {
        if (count($productIds) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('cpp');

        $qb->select('cpp')
            ->where($qb->expr()->eq('cpp.priceList', ':priceList'))
            ->andWhere($qb->expr()->in('cpp.product', ':productIds'))
            ->setParameters([
                'priceList' => $priceList,
                'productIds' => $productIds
            ]);

        if ($currency) {
            $qb->andWhere($qb->expr()->eq('cpp.currency', ':currency'))
                ->setParameter('currency', $currency);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param CombinedPriceList $combinedPriceList
     * @param boolean $mergeAllowed
     */
    protected function addUniquePriceCondition(
        QueryBuilder $qb,
        CombinedPriceList $combinedPriceList,
        $mergeAllowed
    ) {
        if ($mergeAllowed) {
            $this->addProductsBlockedByMergeFlagRestriction($qb, $combinedPriceList);
            $this->addPresentPricesRestriction($qb, $combinedPriceList);
        } else {
            $this->addPresentProductsRestriction($qb, $combinedPriceList);
        }
    }

    /**
     * Filter out product prices by unit, quantity and currency that are already in the CPL.
     * ProductPrice table alias should be pp.
     */
    private function addPresentPricesRestriction(
        QueryBuilder $qb,
        CombinedPriceList $combinedPriceList
    ) {
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('cpp2.id')
            ->from(CombinedProductPrice::class, 'cpp2')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp2.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp2.product'),
                    $qb->expr()->eq('pp.currency', 'cpp2.currency'),
                    $qb->expr()->eq('pp.unit', 'cpp2.unit'),
                    $qb->expr()->eq('pp.quantity', 'cpp2.quantity')
                )
            );

        $qb->andWhere($qb->expr()->not($qb->expr()->exists($subQuery->getQuery()->getDQL())));
        $qb->setParameter('combinedPriceList', $combinedPriceList->getId());
    }

    /**
     * Filter out products that already have prices added with mergeAllowed = false.
     * ProductPrice table alias should be pp.
     */
    private function addProductsBlockedByMergeFlagRestriction(
        QueryBuilder $qb,
        CombinedPriceList $combinedPriceList
    ) {
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('cpp.id')
            ->from(CombinedProductPrice::class, 'cpp')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp.product'),
                    $subQuery->expr()->eq('cpp.mergeAllowed', ':mergeAllowed')
                )
            );

        $qb->andWhere($qb->expr()->not($qb->expr()->exists($subQuery->getQuery()->getDQL())));
        $qb->setParameter('combinedPriceList', $combinedPriceList->getId())
            ->setParameter('mergeAllowed', false);
    }

    /**
     * Filter out prices for products that are already present in the CPL.
     * ProductPrice table alias should be pp.
     */
    private function addPresentProductsRestriction(
        QueryBuilder $qb,
        CombinedPriceList $combinedPriceList
    ) {
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('cpp.id')
            ->from(CombinedProductPrice::class, 'cpp')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp.product')
                )
            );

        $qb->andWhere($qb->expr()->not($qb->expr()->exists($subQuery->getQuery()->getDQL())));
        $qb->setParameter('combinedPriceList', $combinedPriceList->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function findByPriceListIdAndProductIds(
        ShardManager $shardManager,
        $priceListId,
        array $productIds,
        $getTierPrices = true,
        $currency = null,
        $productUnitCode = null,
        array $orderBy = ['unit' => 'ASC', 'quantity' => 'ASC']
    ) {
        if (!$productIds) {
            return [];
        }

        $qb = $this->getFindByPriceListIdAndProductIdsQueryBuilder(
            $priceListId,
            $productIds,
            $getTierPrices,
            $currency,
            $productUnitCode,
            $orderBy
        );
        $qb
            ->addSelect('product', 'unitPrecisions', 'unit')
            ->leftJoin('price.product', 'product')
            ->leftJoin('product.unitPrecisions', 'unitPrecisions')
            ->leftJoin('unitPrecisions.unit', 'unit');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param integer $websiteId
     * @param Product[] $products
     * @param CombinedPriceList $configCpl
     * @return array
     */
    public function findMinByWebsiteForFilter($websiteId, array $products, $configCpl)
    {
        $qb = $this->getQbForMinimalPrices($websiteId, $products, $configCpl);
        $qb->select(
            'IDENTITY(mp.product) as product',
            'MIN(mp.value) as value',
            'mp.currency',
            'IDENTITY(mp.priceList) as cpl',
            'IDENTITY(mp.unit) as unit'
        );
        $qb->groupBy('mp.priceList, mp.product, mp.currency, mp.unit');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param integer $websiteId
     * @param Product[] $products
     * @param CombinedPriceList $configCpl
     * @return array
     */
    public function findMinByWebsiteForSort($websiteId, array $products, $configCpl)
    {
        $qb = $this->getQbForMinimalPrices($websiteId, $products, $configCpl);
        $qb->select(
            'IDENTITY(mp.product) as product',
            'MIN(mp.value) as value',
            'mp.currency',
            'IDENTITY(mp.priceList) as cpl'
        );
        $qb->groupBy('mp.priceList, mp.product, mp.currency');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param ShardManager $shardManager
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function insertMinimalPricesByPriceList(
        ShardManager $shardManager,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        array $products = []
    ) {
        //remove prices that are greater of prices from current PriceList
        $this->deleteInvalidPricesForMinimalStrategy($shardManager, $combinedPriceList, $priceList, $products);

        //insert all prices to free slots
        $this->insertPricesByPriceList(
            $insertFromSelectQueryExecutor,
            $combinedPriceList,
            $priceList,
            true,
            $products
        );
    }

    public function insertMinimalPricesByCombinedPriceList(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $tailCpl
    ) {
        //remove prices that are greater of prices from current PriceList
        $this->deleteInvalidPricesForMinimalStrategyByCpl($combinedPriceList, $tailCpl);

        //insert all prices to free slots
        $this->insertPricesByCombinedPriceList(
            $insertFromSelectQueryExecutor,
            $combinedPriceList,
            $tailCpl
        );
    }

    /**
     * @param int $websiteId
     * @param array $products
     * @param CombinedPriceList $configCpl
     * @return QueryBuilder
     */
    protected function getQbForMinimalPrices($websiteId, array $products, $configCpl)
    {
        $qb = $this->createQueryBuilder('mp');

        return $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('mp.priceList', ':cplIds'),
                    $qb->expr()->in('mp.product', ':products')
                )
            )
            ->setParameter('cplIds', $this->getCplIdsForWebsite($websiteId, $configCpl))
            ->setParameter(
                'products',
                array_map(
                    function ($product) {
                        return (int)($product instanceof Product ? $product->getId() : $product);
                    },
                    $products
                )
            );
    }

    /**
     * @param int $websiteId
     * @param CombinedPriceList|null $configCpl
     * @return array|int[]
     */
    private function getCplIdsForWebsite($websiteId, $configCpl = null)
    {
        $em = $this->getEntityManager();

        $qb = new UnionQueryBuilder($em, false);
        $qb->addSelect('cplId', 'id', Types::INTEGER)
            ->addOrderBy('id');

        if ($configCpl) {
            $subQb = $em->getRepository(CombinedPriceList::class)->createQueryBuilder('cpl');
            $subQb->select('cpl.id AS cplId')
                ->where(
                    $subQb->expr()->eq('cpl.id', ':configCpl')
                )
                ->setParameter('configCpl', $configCpl)
                ->setMaxResults(1);

            $qb->addSubQuery($subQb->getQuery());
        }

        $cplRelations = [
            CombinedPriceListToWebsite::class,
            CombinedPriceListToCustomerGroup::class,
            CombinedPriceListToCustomer::class
        ];

        foreach ($cplRelations as $entityClass) {
            $subQb = $em->getRepository($entityClass)->createQueryBuilder('relation');
            $subQb->select('IDENTITY(relation.priceList) AS cplId')
                ->where(
                    $subQb->expr()->eq('relation.website', ':websiteId')
                )
                ->setParameter('websiteId', $websiteId);

            $qb->addSubQuery($subQb->getQuery());
        }

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }

    /**
     * @param ShardManager $shardManager
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    protected function deleteInvalidPricesForMinimalStrategy(
        ShardManager $shardManager,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        array $products = []
    ) {
        $invalidPricesQb = $this->createQueryBuilder('cpp');
        $invalidPricesQb->select('DISTINCT cpp.id')
            ->where('cpp.priceList = :cpl')
            ->setParameter('cpl', $combinedPriceList);
        $invalidPricesQb->join(
            ProductPrice::class,
            'pp',
            Join::WITH,
            $invalidPricesQb->expr()->andX(
                $invalidPricesQb->expr()->eq('pp.priceList', ':priceList'),
                $invalidPricesQb->expr()->eq('pp.product', 'cpp.product'),
                $invalidPricesQb->expr()->eq('pp.unit', 'cpp.unit'),
                $invalidPricesQb->expr()->eq('pp.quantity', 'cpp.quantity'),
                $invalidPricesQb->expr()->eq('pp.currency', 'cpp.currency'),
                $invalidPricesQb->expr()->lt('pp.value', 'cpp.value')
            )
        );
        $invalidPricesQb->setParameter('priceList', $priceList);

        if (!$products) {
            $this->deleteInvalidByRange($shardManager, $invalidPricesQb, $priceList);
        } else {
            $this->deleteInvalidByProducts($shardManager, $invalidPricesQb, $priceList, $products);
        }
    }

    protected function deleteInvalidPricesForMinimalStrategyByCpl(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $priceList
    ) {
        $invalidPricesQb = $this->createQueryBuilder('cpp');
        $invalidPricesQb->select('DISTINCT cpp.id')
            ->where('cpp.priceList = :cpl')
            ->setParameter('cpl', $combinedPriceList);
        $invalidPricesQb->join(
            $this->_entityName,
            'icpp',
            Join::WITH,
            $invalidPricesQb->expr()->andX(
                $invalidPricesQb->expr()->eq('icpp.priceList', ':priceList'),
                $invalidPricesQb->expr()->eq('icpp.product', 'cpp.product'),
                $invalidPricesQb->expr()->eq('icpp.unit', 'cpp.unit'),
                $invalidPricesQb->expr()->eq('icpp.quantity', 'cpp.quantity'),
                $invalidPricesQb->expr()->eq('icpp.currency', 'cpp.currency'),
                $invalidPricesQb->expr()->lt('icpp.value', 'cpp.value')
            )
        );
        $invalidPricesQb->setParameter('priceList', $priceList);

        $this->deleteInvalidByRangeByCpl($invalidPricesQb, $priceList);
    }

    protected function deletePricesByIds(array $prices)
    {
        if (empty($prices)) {
            return;
        }
        $this->_em->createQueryBuilder()
            ->delete($this->_entityName, 'prices')
            ->where('prices.id IN(:prices)')
            ->setParameter('prices', $prices)
            ->getQuery()
            ->execute();
    }

    /**
     * {@inheritDoc}
     */
    protected function getPriceListIdsByProduct(Product $product)
    {
        $qb = $this->createQueryBuilder('productToPriceList');

        $result = $qb->select('DISTINCT IDENTITY(productToPriceList.priceList) as priceListId')
            ->where('productToPriceList.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }

    private function deleteInvalidByRange(
        ShardManager $shardManager,
        QueryBuilder $invalidPricesQb,
        PriceList $priceList
    ) {
        $minProductId = null;
        $maxProductId = null;
        $invalidPricesQb->andWhere(
            $invalidPricesQb->expr()->between('cpp.product', ':product_min', ':product_max')
        );
        $productPriceQb = $this->getEntityManager()
            ->createQueryBuilder();
        $productPriceQb->select('MIN(IDENTITY(ptp.product))')
            ->from(PriceListToProduct::class, 'ptp')
            ->where('ptp.priceList = :priceList')
            ->setParameter('priceList', $priceList);

        $minProductId = $productPriceQb->getQuery()->getSingleScalarResult();
        $maxProductId = $productPriceQb->select('MAX(IDENTITY(ptp.product))')->getQuery()->getSingleScalarResult();
        while ($minProductId <= $maxProductId) {
            $currentMax = $minProductId + self::BATCH_SIZE;
            if ($currentMax > $maxProductId) {
                $currentMax = $maxProductId;
            }
            $invalidPricesQb->setParameter('product_min', $minProductId)
                ->setParameter('product_max', $currentMax);
            $query = $invalidPricesQb->getQuery();
            $query->setHint('priceList', $priceList->getId());
            $query->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $shardManager);
            $ids = $query->getScalarResult();
            $this->deletePricesByIds($ids);
            // +1 because between operator includes boundary values
            $minProductId = $currentMax + 1;
        }
    }

    private function deleteInvalidByRangeByCpl(
        QueryBuilder $invalidPricesQb,
        CombinedPriceList $priceList
    ) {
        $invalidPricesQb->andWhere(
            $invalidPricesQb->expr()->between('cpp.product', ':product_min', ':product_max')
        );
        $productPriceQb = $this->getEntityManager()
            ->createQueryBuilder();
        $productPriceQb->select('MIN(IDENTITY(ptp.product))')
            ->from($this->_entityName, 'ptp')
            ->where('ptp.priceList = :priceList')
            ->setParameter('priceList', $priceList);

        $minProductId = $productPriceQb->getQuery()->getSingleScalarResult();
        $maxProductId = $productPriceQb->select('MAX(IDENTITY(ptp.product))')->getQuery()->getSingleScalarResult();
        while ($minProductId <= $maxProductId) {
            $currentMax = $minProductId + self::BATCH_SIZE;
            if ($currentMax > $maxProductId) {
                $currentMax = $maxProductId;
            }
            $invalidPricesQb->setParameter('product_min', $minProductId)
                ->setParameter('product_max', $currentMax);
            $query = $invalidPricesQb->getQuery();
            $ids = $query->getScalarResult();
            $this->deletePricesByIds($ids);
            // +1 because between operator includes boundary values
            $minProductId = $currentMax + 1;
        }
    }

    /**
     * @param ShardManager $shardManager
     * @param QueryBuilder $invalidPricesQb
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    private function deleteInvalidByProducts(
        ShardManager $shardManager,
        QueryBuilder $invalidPricesQb,
        PriceList $priceList,
        array $products
    ) {
        $invalidPricesQb->andWhere(
            $invalidPricesQb->expr()->in('cpp.product', ':products')
        );
        $invalidPricesQb->setParameter('products', $products);
        $query = $invalidPricesQb->getQuery();
        $query->setHint('priceList', $priceList->getId());
        $query->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $ids = $query->getScalarResult();
        $this->deletePricesByIds($ids);
    }

    private function insertByProductsRange(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        PriceList $priceList,
        QueryBuilder $qb
    ) {
        $this->insertByProductsRangeForBaseProductPrice(
            $insertFromSelectQueryExecutor,
            PriceListToProduct::class,
            $priceList,
            $qb
        );
    }

    private function insertByProductsRangeByCpl(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $sourceCpl,
        QueryBuilder $qb
    ) {
        $this->insertByProductsRangeForBaseProductPrice(
            $insertFromSelectQueryExecutor,
            CombinedProductPrice::class,
            $sourceCpl,
            $qb
        );
    }

    private function insertByProductsRangeForBaseProductPrice(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        string $priceToProductRelationClass,
        BasePriceList $priceList,
        QueryBuilder $qb
    ) {
        $qb->andWhere($qb->expr()->between('pp.product', ':product_min', ':product_max'));

        $productPriceQb = $this->getEntityManager()
            ->createQueryBuilder();
        $productPriceQb->select('MIN(IDENTITY(ptp.product))')
            ->from($priceToProductRelationClass, 'ptp')
            ->where('ptp.priceList = :priceList')
            ->setParameter('priceList', $priceList);

        $minProductId = $productPriceQb->getQuery()
            ->getSingleScalarResult();
        $maxProductId = $productPriceQb->select('MAX(IDENTITY(ptp.product))')
            ->getQuery()
            ->getSingleScalarResult();

        $this->insertByRange($insertFromSelectQueryExecutor, $qb, $minProductId, $maxProductId);
    }

    /**
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param QueryBuilder $qb
     * @param int $minProductId
     * @param int $maxProductId
     */
    private function insertByRange(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        QueryBuilder $qb,
        $minProductId,
        $maxProductId
    ) {
        while ($minProductId <= $maxProductId) {
            $currentMax = $minProductId + self::BATCH_SIZE;
            if ($currentMax > $maxProductId) {
                $currentMax = $maxProductId;
            }
            $qb->setParameter('product_min', $minProductId)
                ->setParameter('product_max', $currentMax);

            $this->insertToCombinedPricesFromQb($insertFromSelectQueryExecutor, $qb);
            // +1 because between operator includes boundary values
            $minProductId = $currentMax + 1;
        }
    }

    private function insertByProducts(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        QueryBuilder $qb,
        array $products
    ) {
        $qb->andWhere($qb->expr()->in('pp.product', ':products'))
            ->setParameter('products', $products);

        $this->insertToCombinedPricesFromQb($insertFromSelectQueryExecutor, $qb);
    }

    private function getPricesQb(
        CombinedPriceList $combinedPriceList,
        bool $mergeAllowed,
        PriceList $priceList
    ): QueryBuilder {
        $qb = $this->getEntityManager()
            ->getRepository(ProductPrice::class)
            ->createQueryBuilder('pp');

        $qb
            ->select(
                'IDENTITY(pp.product)',
                'IDENTITY(pp.unit)',
                (string)$qb->expr()->literal($combinedPriceList->getId()),
                'pp.productSku',
                'pp.quantity',
                'pp.value',
                'pp.currency',
                sprintf('CAST(%d as boolean)', (int)$mergeAllowed),
                'pp.id',
                'UUID()'
            )
            ->where($qb->expr()->eq('pp.priceList', ':currentPriceList'))
            ->setParameter('currentPriceList', $priceList);

        return $qb;
    }

    private function insertToCombinedPricesFromQb(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        QueryBuilder $qb
    ): void {
        $insertFromSelectQueryExecutor->execute(
            CombinedProductPrice::class,
            [
                'product',
                'unit',
                'priceList',
                'productSku',
                'quantity',
                'value',
                'currency',
                'mergeAllowed',
                'originPriceId',
                'id'
            ],
            $qb
        );
    }

    private function doInsertByProducts(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        PriceList $priceList,
        array $products,
        QueryBuilder $qb
    ): void {
        if (!$products) {
            $this->insertByProductsRange($insertFromSelectQueryExecutor, $priceList, $qb);
        } else {
            $this->insertByProducts($insertFromSelectQueryExecutor, $qb, $products);
        }
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function doInsertByProductsUsingTempTable(
        TempTableManipulatorInterface $tempTableManipulator,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        array $products,
        QueryBuilder $qb,
        string $insertToTableName,
        bool $applyOnDuplicateKeyUpdate = true,
        ?array $tempTableAliases = []
    ): void {
        if (!$products) {
            $this->insertByProductsRangeForBaseProductPriceToTempTable(
                $tempTableManipulator,
                $combinedPriceList,
                PriceListToProduct::class,
                $priceList,
                $qb,
                $insertToTableName,
                $applyOnDuplicateKeyUpdate,
                $tempTableAliases
            );
        } else {
            $qb->andWhere($qb->expr()->in('pp.product', ':products'))
                ->setParameter('products', $products);

            $this->insertFromQbUsingCombinedPricesTempTable(
                $tempTableManipulator,
                $combinedPriceList,
                $qb,
                $insertToTableName,
                $applyOnDuplicateKeyUpdate,
                $tempTableAliases
            );
        }
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function insertByProductsRangeForBaseProductPriceToTempTable(
        TempTableManipulatorInterface $tempTableManipulator,
        CombinedPriceList $combinedPriceList,
        string $priceToProductRelationClass,
        BasePriceList $priceList,
        QueryBuilder $qb,
        string $insertToTableName,
        bool $applyOnDuplicateKeyUpdate = true,
        ?array $tempTableAliases = []
    ) {
        $qb->andWhere($qb->expr()->between('pp.product', ':product_min', ':product_max'));

        $productPriceQb = $this->getEntityManager()
            ->createQueryBuilder();
        $productPriceQb->select('MIN(IDENTITY(ptp.product))')
            ->from($priceToProductRelationClass, 'ptp')
            ->where('ptp.priceList = :priceList')
            ->setParameter('priceList', $priceList);

        $minProductId = $productPriceQb->getQuery()
            ->getSingleScalarResult();
        $maxProductId = $productPriceQb->select('MAX(IDENTITY(ptp.product))')
            ->getQuery()
            ->getSingleScalarResult();

        while ($minProductId <= $maxProductId) {
            $currentMax = $minProductId + self::BATCH_SIZE;
            if ($currentMax > $maxProductId) {
                $currentMax = $maxProductId;
            }
            $qb->setParameter('product_min', $minProductId)
                ->setParameter('product_max', $currentMax);

            $this->insertFromQbUsingCombinedPricesTempTable(
                $tempTableManipulator,
                $combinedPriceList,
                $qb,
                $insertToTableName,
                $applyOnDuplicateKeyUpdate,
                $tempTableAliases
            );
            // +1 because between operator includes boundary values
            $minProductId = $currentMax + 1;
        }
    }

    private function insertFromQbUsingCombinedPricesTempTable(
        TempTableManipulatorInterface $tempTableManipulator,
        CombinedPriceList $combinedPriceList,
        QueryBuilder $qb,
        string $insertToTableName,
        bool $applyOnDuplicateKeyUpdate = true,
        ?array $tempTableAliases = []
    ): void {
        $tempTableManipulator->insertData(
            $insertToTableName,
            CombinedProductPrice::class,
            $combinedPriceList->getId(),
            [
                'product',
                'unit',
                'priceList',
                'productSku',
                'quantity',
                'value',
                'currency',
                'mergeAllowed',
                'originPriceId',
                'id'
            ],
            $qb,
            $applyOnDuplicateKeyUpdate,
            $tempTableAliases
        );
    }
}
