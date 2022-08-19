<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Id\UuidGenerator;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;

/**
 * Entity repository for ProductPrice entity
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductPriceRepository extends BaseProductPriceRepository
{
    const BUFFER_SIZE = 500;

    /**
     * @var UuidGenerator
     */
    protected $uuidGenerator;

    /**
     * @param ShardManager $shardManager
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function deleteGeneratedPrices(
        ShardManager $shardManager,
        PriceList $priceList,
        array $products = []
    ) {
        $qb = $this->getDeleteQbByPriceList($priceList, $products);
        $query = $qb->andWhere($qb->expr()->isNotNull('productPrice.priceRule'))->getQuery();
        $shardName = $shardManager->getEnabledShardName($this->getClassName(), ['priceList' => $priceList]);
        $realTableName = ' ' . $shardName . ' ';
        $baseTable = ' ' . $shardManager->getEntityBaseTable($this->getClassName()) . ' ';
        $sql = $query->getSQL();
        $sql = str_replace($baseTable, $realTableName, $sql);
        $parameters = [$priceList->getId()];
        $types = [\PDO::PARAM_INT];
        if ($products) {
            $parameters[] = array_map(
                function ($product) {
                    return $product instanceof Product ? $product->getId() : $product;
                },
                $products
            );
            $types[] = Connection::PARAM_INT_ARRAY;
        }
        $this->_em->getConnection()->executeQuery($sql, $parameters, $types);
    }

    public function deleteInvalidPrices(ShardManager $shardManager, PriceList $priceList)
    {
        $this->deleteInvalidPricesByProducts($shardManager, $priceList);
    }

    public function deleteInvalidPricesByProducts(
        ShardManager $shardManager,
        PriceList $priceList,
        array $products = []
    ) {
        $qb = $this->createQueryBuilder('invalidPrice');
        $qb->select('invalidPrice.id')
            ->leftJoin(
                PriceListToProduct::class,
                'productRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('invalidPrice.priceList', 'productRelation.priceList'),
                    $qb->expr()->eq('invalidPrice.product', 'productRelation.product')
                )
            )
            ->where($qb->expr()->eq('invalidPrice.priceList', ':priceList'))
            ->andWhere($qb->expr()->isNull('productRelation.id'))
            ->setParameter('priceList', $priceList);

        if ($products) {
            $qb->andWhere($qb->expr()->in('invalidPrice.product', ':products'))
                ->setParameter('products', $products);
        }

        $query = $qb->getQuery();

        $query->setHint('priceList', $priceList->getId());
        $query->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $shardManager);

        $iterator = new BufferedQueryResultIterator($query);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);
        $iterator->setBufferSize(self::BUFFER_SIZE);

        $ids = [];
        $i = 0;

        $qbDelete = $this->getDeleteQbByPriceList($priceList);
        $qbDelete->andWhere('productPrice.id IN (:ids)');
        $sql = $qbDelete->getQuery()->getSQL();
        $baseTableName = ' ' . $shardManager->getEntityBaseTable($this->getClassName()) . ' ';
        $tableName = ' ' . $shardManager->getEnabledShardName($this->getClassName(), ['priceList' => $priceList]) . ' ';
        $sql = str_replace($baseTableName, $tableName, $sql);
        foreach ($iterator as $priceId) {
            $i++;
            $ids[] = $priceId['id'];
            if ($i % self::BUFFER_SIZE === 0) {
                $this->_em->getConnection()->executeQuery(
                    $sql,
                    [$priceList->getId(), $ids],
                    [\PDO::PARAM_INT, Connection::PARAM_STR_ARRAY]
                );
                $ids = [];
            }
        }

        if (!empty($ids)) {
            $this->_em->getConnection()->executeQuery(
                $sql,
                [$priceList->getId(), $ids],
                [\PDO::PARAM_INT, Connection::PARAM_STR_ARRAY]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createQBForCopy(BasePriceList $sourcePriceList, BasePriceList $targetPriceList)
    {
        $qb = parent::createQBForCopy($sourcePriceList, $targetPriceList);
        $qb->andWhere($qb->expr()->isNull('productPrice.priceRule'));

        return $qb;
    }

    public function copyPrices(
        BasePriceList $sourcePriceList,
        BasePriceList $targetPriceList,
        ShardQueryExecutorInterface $insertQueryExecutor
    ) {
        $qb = $this->createQBForCopy($sourcePriceList, $targetPriceList);
        $qb->addSelect('UUID()');

        $fields = [
            'product',
            'unit',
            'priceList',
            'productSku',
            'quantity',
            'value',
            'currency',
            'id',
        ];

        $insertQueryExecutor->execute($this->getClassName(), $fields, $qb);
    }

    /**
     * @param ShardManager $shardManager
     * @param PriceList $priceList
     * @param array $productSkus
     * @return ProductPrice[]
     */
    public function findByPriceListAndProductSkus(ShardManager $shardManager, PriceList $priceList, array $productSkus)
    {
        $qb = $this->createQueryBuilder('price');

        // ensure all skus are strings to avoid postgres's "No operator matches the given name and argument type(s)."
        array_walk($productSkus, function (&$sku) {
            $sku = (string)$sku;
        });

        $query = $qb->leftJoin('price.product', 'product')
            ->andWhere('product.sku in (:productSkus)')
            ->andWhere('price.priceList = :priceList')
            ->addOrderBy($qb->expr()->asc('price.unit'))
            ->addOrderBy($qb->expr()->asc('price.currency'))
            ->addOrderBy($qb->expr()->asc('price.quantity'))
            ->setParameters([
                'productSkus' => $productSkus,
                'priceList' => $priceList
            ])
            ->getQuery();

        $query->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $shardManager);

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductIdsByPriceLists(array $priceLists)
    {
        $this->_em->createQueryBuilder();
        $qb = $this->_em->createQueryBuilder();

        $result = $qb->select('IDENTITY(productToPriceList.product) as productId')
            ->from(PriceListToProduct::class, 'productToPriceList')
            ->where('productToPriceList.priceList IN (:priceLists)')
            ->setParameter('priceLists', $priceLists)
            ->getQuery()
            ->getScalarResult();

        return array_map(fn ($value) => (int) current($value), $result);
    }

    public function remove(ShardManager $shardManager, BaseProductPrice $price)
    {
        $tableName = $shardManager->getEnabledShardName($this->_entityName, ['priceList' => $price->getPriceList()]);
        $connection = $this->_em->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->delete($tableName)
            ->where('id = :id')
            ->setParameter('id', $price->getId())
            ->execute();
    }

    /**
     * @param ShardManager $shardManager
     * @param BaseProductPrice|ProductPrice $price
     */
    public function save(ShardManager $shardManager, BaseProductPrice $price)
    {
        $connection = $this->_em->getConnection();
        $qb = $connection->createQueryBuilder();
        $tableName = $shardManager->getEnabledShardName($this->_entityName, ['priceList' => $price->getPriceList()]);
        $columns = [
            'price_rule_id' => ':price_rule_id',
            'unit_code' => ':unit_code',
            'product_id' => ':product_id',
            'price_list_id' => ':price_list_id',
            'product_sku' => ':product_sku',
            'quantity' => ':quantity',
            'value' => ':value',
            'currency' => ':currency',
            'version' => ':version'
        ];
        if ($price->getId()) {
            $qb->update($tableName, 'price');
            foreach ($columns as $column => $placeholder) {
                $qb->set($column, $placeholder);
            }
            $qb->where('id = :id')
                ->setParameter('id', $price->getId());
        } else {
            $id = $this->getGenerator()->generate($this->_em, null);
            $columns['id'] = ':id';
            $qb->setParameter('id', $id);
            $qb->insert($tableName)
                ->values($columns);
            $price->setId($id);
        }
        $qb
            ->setParameter('price_rule_id', $price->getPriceRule() ? $price->getPriceRule()->getId() : null)
            ->setParameter('unit_code', $price->getProductUnitCode())
            ->setParameter('product_id', $price->getProduct()->getId())
            ->setParameter('price_list_id', $price->getPriceList()->getId())
            ->setParameter('product_sku', $price->getProductSku())
            ->setParameter('quantity', $price->getQuantity())
            ->setParameter('value', $price->getPrice()->getValue())
            ->setParameter('currency', $price->getPrice()->getCurrency())
            ->setParameter('version', $price->getVersion());
        $qb->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        throw new \LogicException('Method locked because of sharded tables');
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        throw new \LogicException('Method locked because of sharded tables');
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        throw new \LogicException('Method locked because of sharded tables');
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        throw new \LogicException('Method locked because of sharded tables');
    }

    /**
     * @param ShardManager $shardManager
     * @param int $priceList
     * @param int $version
     * @param int $batchSize
     * @return \Generator
     */
    public function getProductsByPriceListAndVersion(
        ShardManager $shardManager,
        int $priceList,
        int $version,
        int $batchSize = self::BUFFER_SIZE
    ) {
        $tableName = $shardManager->getEnabledShardName($this->_entityName, ['priceList' => $priceList]);
        $connection = $this->_em->getConnection();
        $qb = $connection->createQueryBuilder();

        $qb->select('DISTINCT pp.product_id')
            ->from($tableName, 'pp')
            ->where($qb->expr()->eq('pp.price_list_id', ':priceListId'))
            ->andWhere($qb->expr()->eq('pp.version', ':version'))
            ->setParameter('priceListId', $priceList)
            ->setParameter('version', $version);

        $stmt = $qb->execute();

        $batch = [];
        $count = 0;
        while ($productId = $stmt->fetchColumn()) {
            $batch[] = $productId;
            $count++;
            if ($batchSize === $count) {
                yield $batch;

                $batch = [];
                $count = 0;
            }
        }

        if ($count) {
            yield $batch;
        }
    }

    /**
     * @return UuidGenerator
     */
    protected function getGenerator()
    {
        if (!$this->uuidGenerator) {
            $this->uuidGenerator = new UuidGenerator();
        }

        return $this->uuidGenerator;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPriceListIdsByProduct(Product $product)
    {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb->select('IDENTITY(productToPriceList.priceList) as priceListId')
            ->from(PriceListToProduct::class, 'productToPriceList')
            ->where('productToPriceList.product = :product')
            ->setParameter('product', $product)
            ->orderBy('productToPriceList.priceList')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }

    /**
     * @param Website $website
     * @param Product[] $products
     * @param PriceList|null $basePriceList
     * @param string $accuracy
     * @return array
     */
    public function findMinByWebsiteForFilter(
        Website $website,
        array $products,
        ?PriceList $basePriceList,
        string $accuracy
    ) {
        $qb = $this->getQbForMinimalPrices($website, $products, $basePriceList, $accuracy);
        $qb->select(
            'IDENTITY(mp.product) as product_id',
            'MIN(mp.value) as value',
            'mp.currency',
            'IDENTITY(mp.priceList) as price_list_id',
            'IDENTITY(mp.unit) as unit'
        );
        $qb->groupBy('mp.priceList, mp.product, mp.currency, mp.unit');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param Website $website
     * @param Product[] $products
     * @param PriceList|null $basePriceList
     * @param string $accuracy
     * @return array
     */
    public function findMinByWebsiteForSort(
        Website $website,
        array $products,
        ?PriceList $basePriceList,
        string $accuracy
    ) {
        $qb = $this->getQbForMinimalPrices($website, $products, $basePriceList, $accuracy);
        $qb->select(
            'IDENTITY(mp.product) as product_id',
            'MIN(mp.value) as value',
            'mp.currency',
            'IDENTITY(mp.priceList) as price_list_id'
        );
        $qb->groupBy('mp.priceList, mp.product, mp.currency');

        return $qb->getQuery()->getArrayResult();
    }

    public function getMinimalPriceIdsQueryBuilder(array $priceLists): QueryBuilder
    {
        $qb = $this->createQueryBuilder('pp');

        $qb
            ->select('MIN(CAST(mp.id as text)) as id')
            ->innerJoin(
                ProductPrice::class,
                'mp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('mp.product', 'pp.product'),
                    $qb->expr()->eq('mp.unit', 'pp.unit'),
                    $qb->expr()->eq('mp.quantity', 'pp.quantity'),
                    $qb->expr()->eq('mp.currency', 'pp.currency')
                )
            )
            ->where($qb->expr()->in('pp.priceList', ':priceLists'))
            ->andWhere($qb->expr()->in('mp.priceList', ':priceLists'))
            ->groupBy('pp.product', 'pp.unit', 'pp.quantity', 'pp.currency', 'mp.value')
            ->having($qb->expr()->eq('mp.value', 'MIN(pp.value)'))
            ->setParameter('priceLists', $priceLists);

        return $qb;
    }

    /**
     * @param Website $website
     * @param array $products
     * @param PriceList|null $basePriceList
     * @param string $accuracy
     * @return QueryBuilder
     * @throws \Doctrine\DBAL\Query\QueryException
     */
    protected function getQbForMinimalPrices(
        Website $website,
        array $products,
        ?PriceList $basePriceList,
        string $accuracy
    ) {
        $qb = $this->createQueryBuilder('mp');

        return $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('mp.priceList', ':priceListIds'),
                    $qb->expr()->in('mp.product', ':products')
                )
            )
            ->setParameter('priceListIds', $this->getPriceListIdsForWebsite($website, $basePriceList, $accuracy))
            ->setParameter(
                'products',
                array_map(
                    static function ($product) {
                        return (int)($product instanceof Product ? $product->getId() : $product);
                    },
                    $products
                )
            );
    }

    /**
     * @param Website $website
     * @param PriceList|null $basePriceList
     * @param string $accuracy
     * @return array|int[]
     * @throws \Doctrine\DBAL\Query\QueryException
     */
    private function getPriceListIdsForWebsite(Website $website, ?PriceList $basePriceList, string $accuracy)
    {
        if ($accuracy === 'website' && $basePriceList) {
            return [$basePriceList->getId()];
        }

        $em = $this->getEntityManager();

        $qb = new UnionQueryBuilder($em, false);
        $qb->addSelect('priceListId', 'id', Types::INTEGER)
            ->addOrderBy('id');

        if ($basePriceList) {
            $subQb = $em->getRepository(PriceList::class)->createQueryBuilder('pl');
            $subQb->select('pl.id AS priceListId')
                ->where(
                    $subQb->expr()->eq('pl.id', ':basePriceList')
                )
                ->setParameter('basePriceList', $basePriceList)
                ->setMaxResults(1);

            $qb->addSubQuery($subQb->getQuery());
        }

        $relations = [
            PriceListToCustomerGroup::class
        ];
        if ($accuracy === 'customer') {
            $relations[] = PriceListToCustomer::class;
        }

        foreach ($relations as $entityClass) {
            $subQb = $em->getRepository($entityClass)->createQueryBuilder('relation');
            $subQb->select('IDENTITY(relation.priceList) AS priceListId')
                ->where(
                    $subQb->expr()->eq('relation.website', ':website')
                )
                ->setParameter('website', $website);

            $qb->addSubQuery($subQb->getQuery());
        }

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }

    public function hasPrices(ShardManager $shardManager, PriceList $priceList): bool
    {
        $tableName = $shardManager->getEnabledShardName($this->_entityName, ['priceList' => $priceList]);
        $connection = $this->_em->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->select('pp.id')
            ->from($tableName, 'pp')
            ->where($qb->expr()->eq('pp.price_list_id', ':priceListId'))
            ->setMaxResults(1)
            ->setParameter('priceListId', $priceList->getId());

        return (bool)$qb->execute()->fetchOne();
    }
}
