<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
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
use Oro\Bundle\PricingBundle\ORM\MultiInsertShardQueryExecutor;
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
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CombinedProductPriceRepository extends BaseProductPriceRepository
{
    private const BATCH_SIZE = 100000;
    private const BATCH_SIZE_GROUP_BY = 10;

    public function copyPricesByPriceList(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        bool $mergeAllowed,
        array $products = []
    ): void {
        $this->doInsertByProducts(
            $insertFromSelectQueryExecutor,
            $priceList,
            $products,
            $this->getPricesQb($combinedPriceList, $mergeAllowed, $priceList)
        );
    }

    public function copyPricesByPriceListWithTempTable(
        TempTableManipulatorInterface $tempTableManipulator,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        bool $mergeAllowed,
        array $products = []
    ): void {
        $tempTableName = $tempTableManipulator->getTempTableNameForEntity(
            CombinedProductPrice::class,
            $combinedPriceList->getId()
        );

        $this->doInsertByProductsUsingTempTable(
            $tempTableManipulator,
            $combinedPriceList,
            $priceList,
            $products,
            $this->getPricesQb($combinedPriceList, $mergeAllowed, $priceList),
            $tempTableName,
            false
        );
    }

    public function insertPricesByPriceListWithTempTable(
        TempTableManipulatorInterface $tempTableManipulator,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        bool $mergeAllowed,
        array $products
    ): void {
        $tempTableName = $tempTableManipulator->getTempTableNameForEntity(
            CombinedProductPrice::class,
            $combinedPriceList->getId()
        );

        $qb = $this->getPricesQb($combinedPriceList, $mergeAllowed, $priceList);
        $this->addUniquePriceCondition($qb, $combinedPriceList, $mergeAllowed);

        $this->doInsertByProductsUsingTempTable(
            $tempTableManipulator,
            $combinedPriceList,
            $priceList,
            $products,
            $qb,
            $tempTableManipulator->getTempTableNameForEntity(CombinedProductPrice::class, $combinedPriceList->getId()),
            true,
            ['cpp' => $tempTableName, 'cpp2' => $tempTableName]
        );
    }

    public function insertPricesByPriceList(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        bool $mergeAllowed,
        array $products = []
    ): void {
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
        CombinedPriceList $sourceCpl,
        array $products = []
    ): void {
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

        $this->doInsertByProductsByCpl(
            $insertFromSelectQueryExecutor,
            $sourceCpl,
            $products,
            $qb
        );
    }

    public function deleteCombinedPrices(CombinedPriceList $combinedPriceList, array $products = []): void
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

    public function deleteDuplicatePrices(array $cpls = []): int
    {
        $delete = <<<SQL
            DELETE FROM oro_price_product_combined cpp1 
            USING oro_price_product_combined cpp2
            WHERE
                cpp1.id < cpp2.id
                AND cpp1.combined_price_list_id = cpp2.combined_price_list_id
                AND cpp1.product_id = cpp2.product_id
                AND cpp1.value = cpp2.value
                AND cpp1.currency = cpp2.currency
                AND cpp1.quantity = cpp2.quantity
                AND cpp1.unit_code = cpp2.unit_code %s
            RETURNING cpp1.id
        SQL;

        $sql = "WITH deleted_rows AS ($delete) SELECT COUNT(*) FROM deleted_rows";

        return $this->executeDuplicatePricesQuery($sql, $cpls);
    }

    public function hasDuplicatePrices(array $cpls = []): bool
    {
        $sql = <<<SQL
            SELECT 1
            FROM oro_price_product_combined cpp1 
            INNER JOIN oro_price_product_combined cpp2 ON 
                cpp1.id < cpp2.id
                AND cpp1.combined_price_list_id = cpp2.combined_price_list_id
                AND cpp1.product_id = cpp2.product_id
                AND cpp1.value = cpp2.value
                AND cpp1.currency = cpp2.currency
                AND cpp1.quantity = cpp2.quantity
                AND cpp1.unit_code = cpp2.unit_code %s
            LIMIT 1
        SQL;

        return (bool) $this->executeDuplicatePricesQuery($sql, $cpls);
    }

    private function executeDuplicatePricesQuery(string $sql, array $cpls = []): int
    {
        $connection = $this->_em->getConnection();
        $parameters = $types = [];
        if ($cpls) {
            $sql = sprintf($sql, 'AND cpp1.combined_price_list_id IN (:combinedPriceLists)');
            $parameters = ['combinedPriceLists' => $cpls];
            $types = ['combinedPriceLists' => Connection::PARAM_INT_ARRAY];
        } else {
            $sql = sprintf($sql, '');
        }

        return (int)$connection->executeQuery($sql, $parameters, $types)->fetchOne();
    }

    /**
     * When merge allowed = true
     *   - include only prices for product quantities that are not yet present.
     *   - skip prices that were added with merge = false
     *
     * When merge allowed = false
     *  - if there are no prices for product yet - include prices with merge allowed = false
     *  - if there is at least one price for product - skip prices for product from PL with merge allowed = false
     */
    protected function addUniquePriceCondition(
        QueryBuilder $qb,
        CombinedPriceList $combinedPriceList,
        bool $mergeAllowed
    ): void {
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
    ): void {
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
    ): void {
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
    ): void {
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

    public function findMinByWebsiteForFilter(
        int $websiteId,
        array $products,
        ?CombinedPriceList $configCpl = null
    ): iterable {
        $cplIds = $this->getCplIdsForWebsite($websiteId, $configCpl);
        foreach (array_chunk($products, self::BATCH_SIZE_GROUP_BY) as $productsBatch) {
            $qb = $this->getQbForMinimalPrices($productsBatch, $cplIds);
            $qb->select(
                'IDENTITY(mp.product) as product',
                'MIN(mp.value) as value',
                'mp.currency',
                'IDENTITY(mp.priceList) as cpl',
                'IDENTITY(mp.unit) as unit'
            );
            $qb->groupBy('mp.priceList, mp.product, mp.currency, mp.unit');

            yield from $qb->getQuery()->getArrayResult();
        }
    }

    public function findMinByWebsiteForSort(
        int $websiteId,
        array $products,
        ?CombinedPriceList $configCpl = null
    ): iterable {
        $cplIds = $this->getCplIdsForWebsite($websiteId, $configCpl);
        foreach (array_chunk($products, self::BATCH_SIZE_GROUP_BY) as $productsBatch) {
            $qb = $this->getQbForMinimalPrices($productsBatch, $cplIds);
            $qb->select(
                'IDENTITY(mp.product) as product',
                'MIN(mp.value) as value',
                'mp.currency',
                'IDENTITY(mp.priceList) as cpl'
            );
            $qb->groupBy('mp.priceList, mp.product, mp.currency');

            yield from $qb->getQuery()->getArrayResult();
        }
    }

    public function insertMinimalPricesByPriceList(
        ShardManager $shardManager,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        array $products = []
    ): void {
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

    public function insertMinimalPricesByPriceLists(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        array $priceLists,
        array $products
    ): void {
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
                sprintf('CAST(%d as boolean)', 1),
                'pp.id',
                'UUID()'
            )
            ->where($qb->expr()->in('pp.id', ':ids'));

        $minimaPriceIdsQb = $this->getEntityManager()
            ->getRepository(ProductPrice::class)
            ->getMinimalPriceIdsQueryBuilder($priceLists);
        if ($products) {
            $minimaPriceIdsQb->andWhere($minimaPriceIdsQb->expr()->in('pp.product', ':products'));
            foreach (array_chunk($products, self::BATCH_SIZE) as $batch) {
                $minimaPriceIdsQb->setParameter('products', $batch);
                $this->insertMinimalPricesInBatches($minimaPriceIdsQb, $qb, $insertFromSelectQueryExecutor);
            }
        } else {
            $minimaPriceIdsQb->andWhere($qb->expr()->between('pp.product', ':product_min', ':product_max'));
            [$minProductId, $maxProductId] = $this->getMinMaxProductIds(PriceListToProduct::class, $priceLists);

            while ($minProductId <= $maxProductId) {
                $currentMax = $minProductId + self::BATCH_SIZE;
                if ($currentMax > $maxProductId) {
                    $currentMax = $maxProductId;
                }
                $minimaPriceIdsQb
                    ->setParameter('product_min', $minProductId)
                    ->setParameter('product_max', $currentMax);
                // +1 because between operator includes boundary values
                $minProductId = $currentMax + 1;

                $this->insertMinimalPricesInBatches($minimaPriceIdsQb, $qb, $insertFromSelectQueryExecutor);
            }
        }
    }

    private function insertMinimalPricesInBatches(
        QueryBuilder $minimaPriceIdsQb,
        QueryBuilder $queryBuilder,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor
    ): void {
        foreach ($this->getMinimalPricesBatchedIds($minimaPriceIdsQb, $insertFromSelectQueryExecutor) as $ids) {
            $queryBuilder->setParameter('ids', $ids);
            $this->insertToCombinedPricesFromQb($insertFromSelectQueryExecutor, $queryBuilder);
        }
    }

    private function getMinimalPricesBatchedIds(
        QueryBuilder $minimaPriceIdsQb,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor
    ): \Generator {
        $result = $minimaPriceIdsQb->getQuery()->getArrayResult();

        if ($insertFromSelectQueryExecutor instanceof MultiInsertShardQueryExecutor) {
            // Reset MultiInsertShardQueryExecutor batch size for minimal prices to control batches in repository
            // and avoid extra selects executed by BufferedIdentityQueryResultIterator
            $originalBatchSize = $insertFromSelectQueryExecutor->getBatchSize();
            $insertFromSelectQueryExecutor->setBatchSize(0);
            foreach (array_chunk($result, $originalBatchSize) as $ids) {
                yield $ids;
            }
            // Restore batch size
            $insertFromSelectQueryExecutor->setBatchSize($originalBatchSize);
        } else {
            yield $result;
        }
    }

    public function insertMinimalPricesByCombinedPriceList(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $tailCpl,
        array $products = []
    ): void {
        //remove prices that are greater of prices from current PriceList
        $this->deleteInvalidPricesForMinimalStrategyByCpl($combinedPriceList, $tailCpl, $products);

        //insert all prices to free slots
        $this->insertPricesByCombinedPriceList(
            $insertFromSelectQueryExecutor,
            $combinedPriceList,
            $tailCpl,
            $products
        );
    }

    protected function getQbForMinimalPrices(
        array $products,
        array $cplIds
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('mp');

        return $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('mp.priceList', ':cplIds'),
                    $qb->expr()->in('mp.product', ':products')
                )
            )
            ->setParameter('cplIds', $cplIds)
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

    private function getCplIdsForWebsite(int $websiteId, ?CombinedPriceList $configCpl = null): array
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
    ): void {
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
        CombinedPriceList $priceList,
        array $products = []
    ): void {
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

        if (!$products) {
            $this->deleteInvalidByRangeByCpl($invalidPricesQb, $priceList);
        } else {
            $this->deleteInvalidByProductsByCpl($invalidPricesQb, $products);
        }
    }

    protected function deletePricesByIds(array $prices): void
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
    ): void {
        $invalidPricesQb->andWhere(
            $invalidPricesQb->expr()->between('cpp.product', ':product_min', ':product_max')
        );

        [$minProductId, $maxProductId] = $this->getMinMaxProductIds(PriceListToProduct::class, [$priceList]);
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
    ): void {
        $invalidPricesQb->andWhere(
            $invalidPricesQb->expr()->between('cpp.product', ':product_min', ':product_max')
        );

        [$minProductId, $maxProductId] = $this->getMinMaxProductIds($this->_entityName, [$priceList]);
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

    private function deleteInvalidByProductsByCpl(
        QueryBuilder $invalidPricesQb,
        array $products
    ): void {
        $invalidPricesQb->andWhere(
            $invalidPricesQb->expr()->in('cpp.product', ':products')
        );
        $invalidPricesQb->setParameter('products', $products);
        $query = $invalidPricesQb->getQuery();

        $ids = $query->getScalarResult();
        $this->deletePricesByIds($ids);
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
    ): void {
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
    ): void {
        $this->insertByProductsRangeForBaseProductPrice(
            $insertFromSelectQueryExecutor,
            PriceListToProduct::class,
            [$priceList],
            $qb
        );
    }

    private function doInsertByProductsByCpl(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $sourceCpl,
        array $products,
        QueryBuilder $qb
    ): void {
        if (!$products) {
            $this->insertByProductsRangeByCpl($insertFromSelectQueryExecutor, $sourceCpl, $qb);
        } else {
            $qb->andWhere($qb->expr()->in('pp.product', ':products'));
            foreach (array_chunk($products, self::BATCH_SIZE) as $productsBatch) {
                $qb->setParameter('products', $productsBatch);

                $this->insertToCombinedPricesFromQb($insertFromSelectQueryExecutor, $qb);
            }
        }
    }

    private function insertByProductsRangeByCpl(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceList $sourceCpl,
        QueryBuilder $qb
    ): void {
        $this->insertByProductsRangeForBaseProductPrice(
            $insertFromSelectQueryExecutor,
            CombinedProductPrice::class,
            [$sourceCpl],
            $qb
        );
    }

    /**
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param string $priceToProductRelationClass
     * @param array|BasePriceList[] $priceLists
     * @param QueryBuilder $qb
     */
    private function insertByProductsRangeForBaseProductPrice(
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        string $priceToProductRelationClass,
        array $priceLists,
        QueryBuilder $qb
    ): void {
        $qb->andWhere($qb->expr()->between('pp.product', ':product_min', ':product_max'));

        [$minProductId, $maxProductId] = $this->getMinMaxProductIds($priceToProductRelationClass, $priceLists);

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
            $qb->andWhere($qb->expr()->in('pp.product', ':products'));
            foreach (array_chunk($products, self::BATCH_SIZE) as $productsBatch) {
                $qb->setParameter('products', $productsBatch);

                $this->insertToCombinedPricesFromQb($insertFromSelectQueryExecutor, $qb);
            }
        }
    }

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

    private function insertByProductsRangeForBaseProductPriceToTempTable(
        TempTableManipulatorInterface $tempTableManipulator,
        CombinedPriceList $combinedPriceList,
        string $priceToProductRelationClass,
        BasePriceList $priceList,
        QueryBuilder $qb,
        string $insertToTableName,
        bool $applyOnDuplicateKeyUpdate = true,
        ?array $tempTableAliases = []
    ): void {
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

    /**
     * @param string $priceToProductRelationClass
     * @param array $priceLists
     * @return array [int, int]
     */
    private function getMinMaxProductIds(string $priceToProductRelationClass, array $priceLists): array
    {
        $productPriceQb = $this->getEntityManager()
            ->createQueryBuilder();
        $productPriceQb
            ->select(
                'MIN(IDENTITY(ptp.product))',
                'MAX(IDENTITY(ptp.product))'
            )
            ->from($priceToProductRelationClass, 'ptp')
            ->where($productPriceQb->expr()->in('ptp.priceList', ':priceLists'))
            ->setParameter('priceLists', $priceLists);

        return array_values((array)$productPriceQb->getQuery()->getSingleResult());
    }
}
