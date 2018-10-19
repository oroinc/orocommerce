<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\EntityNotSupportsShardingException;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

abstract class BaseProductPriceRepository extends EntityRepository
{
    /**
     * @param ShardManager $shardManager
     * @param Product $product
     * @param ProductUnit $unit
     */
    public function deleteByProductUnit(
        ShardManager $shardManager,
        Product $product,
        ProductUnit $unit
    ) {
        // fetch price lists by product
        $priceLists = $this->getPriceListIdsByProduct($product);

        // go through price lists, delete pries by each of them, because of sharding
        $qb = $this->createQueryBuilder('productPrice');

        $qb->delete()
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('productPrice.priceList', ':priceList'),
                    $qb->expr()->eq('productPrice.unit', ':unit'),
                    $qb->expr()->eq('productPrice.product', ':product')
                )
            );
        foreach ($priceLists as $priceListId) {
            $sql = $qb->getQuery()->getSQL();
            $baseTableName = ' ' . $shardManager->getEntityBaseTable($this->getClassName()) . ' ';
            try {
                $shardName = $shardManager->getEnabledShardName($this->getClassName(), ['priceList' => $priceListId]);
                $tableName = ' ' . $shardName . ' ';
            } catch (EntityNotSupportsShardingException $ex) {
                $tableName = $baseTableName;
            }
            $sql = str_replace($baseTableName, $tableName, $sql);
            $this->_em->getConnection()->executeQuery($sql, [$priceListId, $unit, $product->getId()]);
        }
    }

    /**
     * @param ShardManager $shardManager
     * @param BasePriceList $priceList
     * @param array|Product[] $products
     */
    public function deleteByPriceList(
        ShardManager $shardManager,
        BasePriceList $priceList,
        array $products = []
    ) {
        $query = $this->getDeleteQbByPriceList($priceList, $products)
            ->getQuery();
        $sql = $query->getSQL();
        $baseTableName = ' ' . $shardManager->getEntityBaseTable($this->getClassName()) . ' ';
        $tableName = ' ' . $shardManager->getEnabledShardName($this->getClassName(), ['priceList' => $priceList]) . ' ';
        $sql = str_replace($baseTableName, $tableName, $sql);
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

    /**
     * @param BasePriceList $priceList
     *
     * @return int
     */
    public function deletePricesByPriceList(BasePriceList $priceList): int
    {
        return $this->getDeleteQbByPriceList($priceList)
            ->getQuery()
            ->execute();
    }

    /**
     * @param BasePriceList $priceList
     * @param array|Product[] $products
     * @return QueryBuilder
     */
    protected function getDeleteQbByPriceList(BasePriceList $priceList, array $products = [])
    {
        $qb = $this->createQueryBuilder('productPrice');

        $qb->delete()
            ->where($qb->expr()->eq('productPrice.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);

        if ($products) {
            $qb->andWhere($qb->expr()->in('productPrice.product', ':products'))
                ->setParameter('products', $products);
        }

        return $qb;
    }

    /**
     * @param ShardManager $shardManager
     * @param PriceList $priceList
     * @return int
     */
    public function countByPriceList(ShardManager $shardManager, PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('productPrice');

        $query = $qb
            ->select($qb->expr()->count('productPrice.id'))
            ->where($qb->expr()->eq('productPrice.priceList', ':priceList'))
            ->setParameter('priceList', $priceList)
            ->getQuery();
        $query->setHint('priceList', $priceList->getId());
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        return (int)$query
            ->getSingleScalarResult();
    }

    /**
     * @deprecated Fetch currencies from config instead
     * @return array
     */
    public function getAvailableCurrencies()
    {
        $qb = $this->createQueryBuilder('productPrice');

        $currencies = $qb
            ->distinct()
            ->select('productPrice.currency')
            ->orderBy($qb->expr()->asc('productPrice.currency'))
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($currencies as $currency) {
            $currencyName = reset($currency);
            $result[$currencyName] = $currencyName;
        }

        return $result;
    }

    /**
     * @param ShardManager $shardManager
     * @param Product $product
     * @return ProductPrice[]
     */
    public function getPricesByProduct(ShardManager $shardManager, Product $product)
    {
        $priceLists = $this->getPriceListIdsByProduct($product);

        $qb = $this->createQueryBuilder('price');
        $qb->andWhere('price.priceList = :priceList')
            ->andWhere('price.product = :product')
            ->addOrderBy($qb->expr()->asc('price.priceList'))
            ->addOrderBy($qb->expr()->asc('price.unit'))
            ->addOrderBy($qb->expr()->asc('price.currency'))
            ->addOrderBy($qb->expr()->asc('price.quantity'))
            ->setParameter('product', $product);

        $prices = [];
        foreach ($priceLists as $priceListId) {
            $qb->setParameter('priceList', $priceListId);
            $query = $qb->getQuery();
            $query->useQueryCache(false);
            $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

            $prices[] = $query->getResult();
        }

        return array_merge(...$prices);
    }

    /**
     * Return product prices for specified price list and product IDs
     *
     * @param ShardManager $shardManager
     * @param int $priceListId
     * @param array $productIds
     * @param bool $getTierPrices
     * @param string|null $currency
     * @param string|null $productUnitCode
     * @param array $orderBy
     * @return ProductPrice[]
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

        $query = $qb->getQuery();
        $query->useQueryCache(false);
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        return $query->getResult();
    }

    /**
     * Return product prices for specified price list and product IDs
     *
     * @param int $priceListId
     * @param array $productIds
     * @param bool $getTierPrices
     * @param string|null $currency
     * @param string|null $productUnitCode
     * @param array $orderBy
     *
     * @return QueryBuilder
     */
    public function getFindByPriceListIdAndProductIdsQueryBuilder(
        $priceListId,
        array $productIds,
        $getTierPrices = true,
        $currency = null,
        $productUnitCode = null,
        array $orderBy = ['unit' => 'ASC', 'quantity' => 'ASC']
    ) {
        $qb = $this->createQueryBuilder('price');
        $qb
            ->where(
                $qb->expr()->eq('IDENTITY(price.priceList)', ':priceListId'),
                $qb->expr()->in('IDENTITY(price.product)', ':productIds')
            )
            ->setParameter('priceListId', $priceListId)
            ->setParameter('productIds', $productIds);

        if ($currency) {
            $qb
                ->andWhere($qb->expr()->eq('price.currency', ':currency'))
                ->setParameter('currency', $currency);
        }

        if (!$getTierPrices) {
            $qb
                ->andWhere($qb->expr()->eq('price.quantity', ':priceQuantity'))
                ->setParameter('priceQuantity', 1);
        }

        if ($productUnitCode) {
            $qb
                ->andWhere($qb->expr()->eq('IDENTITY(price.unit)', ':productUnitCode'))
                ->setParameter('productUnitCode', $productUnitCode);
        }

        foreach ($orderBy as $fieldName => $orderDirection) {
            $qb->addOrderBy(
                QueryBuilderUtil::getField('price', $fieldName),
                QueryBuilderUtil::getSortOrder($orderDirection)
            );
        }

        return $qb;
    }

    /**
     * @param ShardManager $shardManager
     * @param int $priceListId
     * @param array $productIds
     * @param array|null $productUnitCodes
     * @param array|null $currencies
     * @return array
     */
    public function getPricesBatch(
        ShardManager $shardManager,
        $priceListId,
        array $productIds,
        array $productUnitCodes = null,
        array $currencies = null
    ) {
        if (!$productIds) {
            return [];
        }

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select(
                [
                    'IDENTITY(price.product) as id',
                    'IDENTITY(price.unit) as code',
                    'price.quantity',
                    'price.value',
                    'price.currency'
                ]
            )
            ->from($this->_entityName, 'price')
            ->where(
                $qb->expr()->eq('IDENTITY(price.priceList)', ':priceListId'),
                $qb->expr()->in('IDENTITY(price.product)', ':productIds')
            )
            ->setParameter('priceListId', $priceListId)
            ->setParameter('productIds', $productIds)
            ->addOrderBy('IDENTITY(price.unit)')
            ->addOrderBy('price.quantity');

        if ($productUnitCodes) {
            $qb
                ->andWhere($qb->expr()->in('IDENTITY(price.unit)', ':productUnitCodes'))
                ->setParameter('productUnitCodes', $productUnitCodes);
        }
        if ($currencies) {
            $qb
                ->andWhere($qb->expr()->in('price.currency', ':currencies'))
                ->setParameter('currencies', $currencies);
        }

        $query = $qb->getQuery();
        $query->useQueryCache(false);
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        return $query->getArrayResult();
    }

    /**
     * @param BasePriceList $sourcePriceList
     * @param BasePriceList $targetPriceList
     * @param ShardQueryExecutorInterface $insertQueryExecutor
     */
    public function copyPrices(
        BasePriceList $sourcePriceList,
        BasePriceList $targetPriceList,
        ShardQueryExecutorInterface $insertQueryExecutor
    ) {
        $qb = $this->createQBForCopy($sourcePriceList, $targetPriceList);

        $fields = [
            'product',
            'unit',
            'priceList',
            'productSku',
            'quantity',
            'value',
            'currency',
        ];

        $insertQueryExecutor->execute($this->getClassName(), $fields, $qb);
    }

    /**
     * @param BasePriceList $sourcePriceList
     * @param BasePriceList $targetPriceList
     * @return QueryBuilder
     */
    protected function createQBForCopy(BasePriceList $sourcePriceList, BasePriceList $targetPriceList)
    {
        $qb = $this->createQueryBuilder('productPrice');
        $qb
            ->select(
                'IDENTITY(productPrice.product)',
                'IDENTITY(productPrice.unit)',
                (string)$qb->expr()->literal($targetPriceList->getId()),
                'productPrice.productSku',
                'productPrice.quantity',
                'productPrice.value',
                'productPrice.currency'
            )
            ->where($qb->expr()->eq('productPrice.priceList', ':sourcePriceList'))
            ->setParameter('sourcePriceList', $sourcePriceList);

        return $qb;
    }

    /**
     * @param array|int[]|BasePriceList[] $priceLists
     * @return array
     */
    public function getProductIdsByPriceLists(array $priceLists)
    {
        if (empty($priceLists)) {
            return [];
        }

        $qb = $this->createQueryBuilder('price');
        $qb->select('DISTINCT IDENTITY(price.product) AS product')
            ->where($qb->expr()->in('price.priceList', ':priceLists'))
            ->setParameter('priceLists', $priceLists);

        $result = $qb->getQuery()->getScalarResult();

        return array_map('current', $result);
    }

    /**
     * @param ShardManager $shardManager
     * @param BasePriceList $priceList
     * @param array $criteria
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array|BaseProductPrice[]
     */
    public function findByPriceList(
        ShardManager $shardManager,
        BasePriceList $priceList,
        array $criteria,
        array $orderBy = [],
        $limit = null,
        $offset = null
    ) {
        $qb = $this->createQueryBuilder('prices');
        $qb->andWhere('prices.priceList = :priceList')
            ->setParameter('priceList', $priceList);
        foreach ($criteria as $field => $criterion) {
            QueryBuilderUtil::checkIdentifier($field);
            if ($criterion === null) {
                $qb->andWhere($qb->expr()->isNull('prices.'.$field));
            } elseif (is_array($criterion)) {
                $qb->andWhere($qb->expr()->in('prices.'.$field, $criterion));
            } else {
                $qb->andWhere($qb->expr()->eq('prices.'.$field, ':'.$field))
                    ->setParameter($field, $criterion);
            }
        }
        foreach ($orderBy as $field => $order) {
            $qb->addOrderBy(
                QueryBuilderUtil::getField('prices', $field),
                QueryBuilderUtil::getSortOrder($order)
            );
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        $query = $qb->getQuery();
        $query->setHint('priceList', $priceList->getId());
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        return $query->getResult();
    }


    /**
     * @param Product $product
     * @return array|int[]
     */
    abstract protected function getPriceListIdsByProduct(Product $product);

    /**
     * @param ShardManager $shardManager
     * @param BaseProductPrice $price
     */
    public function save(ShardManager $shardManager, BaseProductPrice $price)
    {
        $this->_em->persist($price);
        $this->_em->flush($price);
    }

    /**
     * @param ShardManager $shardManager
     * @param BaseProductPrice $price
     */
    public function remove(ShardManager $shardManager, BaseProductPrice $price)
    {
        $this->_em->remove($price);
        $this->_em->flush($price);
    }
}
