<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;

/**
 * Doctrine repository for Oro\Bundle\PricingBundle\Entity\CombinedProductPrice entity
 */
class CombinedProductPriceRepository extends BaseProductPriceRepository
{
    const BATCH_SIZE = 10000;

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
        $qb = $this->getEntityManager()
            ->getRepository('OroPricingBundle:ProductPrice')
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
                sprintf('CAST(%d as boolean)', (int)$mergeAllowed)
            )
            ->where($qb->expr()->eq('pp.priceList', ':currentPriceList'))
            ->setParameter('currentPriceList', $priceList);
        $this->addUniquePriceCondition($qb, $combinedPriceList, $mergeAllowed);

        if (!$products) {
            $this->insertByProductsRange($insertFromSelectQueryExecutor, $priceList, $qb);
        } else {
            $this->insertByProducts($insertFromSelectQueryExecutor, $qb, $products);
        }
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
        $subQuery = $this->_em->createQueryBuilder();
        $subQuery->select('cpp.id')
            ->from('OroPricingBundle:CombinedProductPrice', 'cpp')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp.product')
                )
            );
        if ($mergeAllowed) {
            $subQuery->andWhere(
                $subQuery->expr()->eq('cpp.mergeAllowed', ':mergeAllowed')
            );
            $qb->setParameter('mergeAllowed', false);

            $subQuery2 = $this->_em->createQueryBuilder();
            $subQuery2->select('cpp2.id')
                ->from('OroPricingBundle:CombinedProductPrice', 'cpp2')
                ->where(
                    $qb->expr()->andX(
                        $qb->expr()->eq('cpp2.priceList', ':combinedPriceList'),
                        $qb->expr()->eq('pp.product', 'cpp2.product'),
                        $qb->expr()->eq('pp.currency', 'cpp2.currency'),
                        $qb->expr()->eq('pp.unit', 'cpp2.unit'),
                        $qb->expr()->eq('pp.quantity', 'cpp2.quantity')
                    )
                );
            $qb->andWhere($qb->expr()->not($qb->expr()->exists($subQuery2->getQuery()->getDQL())));
        }
        $qb->andWhere($qb->expr()->not($qb->expr()->exists($subQuery->getQuery()->getDQL())))
            ->setParameter('combinedPriceList', $combinedPriceList->getId());
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
        $qb->addSelect('cplId', 'id', Type::INTEGER)
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
            'OroPricingBundle:ProductPrice',
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

    /**
     * @param array $prices
     */
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

    /**
     * @param ShardManager $shardManager
     * @param PriceList    $priceList
     * @param QueryBuilder $invalidPricesQb
     */
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
            ->from('OroPricingBundle:PriceListToProduct', 'ptp')
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
            $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);
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
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);
        $ids = $query->getScalarResult();
        $this->deletePricesByIds($ids);
    }

    /**
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param PriceList $priceList
     * @param QueryBuilder $qb
     */
    private function insertByProductsRange(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        PriceList $priceList,
        QueryBuilder $qb
    ) {
        $minProductId = null;
        $maxProductId = null;
        $qb->andWhere($qb->expr()->between('pp.product', ':product_min', ':product_max'));
        $productPriceQb = $this->getEntityManager()
            ->createQueryBuilder();
        $productPriceQb->select('MIN(IDENTITY(ptp.product))')
            ->from('OroPricingBundle:PriceListToProduct', 'ptp')
            ->where('ptp.priceList = :priceList')
            ->setParameter('priceList', $priceList);

        $minProductId = $productPriceQb->getQuery()->getSingleScalarResult();
        $maxProductId = $productPriceQb->select('MAX(IDENTITY(ptp.product))')->getQuery()->getSingleScalarResult();

        while ($minProductId <= $maxProductId) {
            $currentMax = $minProductId + self::BATCH_SIZE;
            if ($currentMax > $maxProductId) {
                $currentMax = $maxProductId;
            }
            $qb->setParameter('product_min', $minProductId)
                ->setParameter('product_max', $currentMax);

            $insertFromSelectQueryExecutor->execute(
                'OroPricingBundle:CombinedProductPrice',
                [
                    'product',
                    'unit',
                    'priceList',
                    'productSku',
                    'quantity',
                    'value',
                    'currency',
                    'mergeAllowed'
                ],
                $qb
            );
            // +1 because between operator includes boundary values
            $minProductId = $currentMax + 1;
        }
    }

    /**
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param QueryBuilder                $qb
     * @param array                       $products
     */
    private function insertByProducts(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        QueryBuilder $qb,
        array $products
    ) {
        $qb->andWhere($qb->expr()->in('pp.product', ':products'))
            ->setParameter('products', $products);
        $insertFromSelectQueryExecutor->execute(
            'OroPricingBundle:CombinedProductPrice',
            [
                'product',
                'unit',
                'priceList',
                'productSku',
                'quantity',
                'value',
                'currency',
                'mergeAllowed'
            ],
            $qb
        );
    }
}
