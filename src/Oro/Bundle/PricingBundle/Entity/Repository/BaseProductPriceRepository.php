<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectShardQueryExecutor;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

abstract class BaseProductPriceRepository extends EntityRepository
{
    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param Product $product
     * @param ProductUnit $unit
     */
    public function deleteByProductUnit(
        QueryHintResolverInterface $hintResolver,
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
            $qb->setParameter('priceList', $priceListId)
                ->setParameter('unit', $unit)
                ->setParameter('product', $product);

            $query = $qb->getQuery();
            $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);
            $query->execute();
        }
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param BasePriceList $priceList
     * @param Product $product
     */
    public function deleteByPriceList(
        QueryHintResolverInterface $hintResolver,
        BasePriceList $priceList,
        Product $product = null
    ) {
        $query = $this->getDeleteQbByPriceList($priceList, $product)
            ->getQuery();
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);
        $query->execute();
    }

    /**
     * @param BasePriceList $priceList
     * @param Product|null $product
     * @return QueryBuilder
     */
    protected function getDeleteQbByPriceList(BasePriceList $priceList, Product $product = null)
    {
        $qb = $this->createQueryBuilder('productPrice');

        $qb->delete()
            ->where($qb->expr()->eq('productPrice.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);

        if ($product) {
            $qb->andWhere($qb->expr()->eq('productPrice.product', ':product'))
                ->setParameter('product', $product);
        }

        return $qb;
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param PriceList $priceList
     * @return int
     */
    public function countByPriceList(QueryHintResolverInterface $hintResolver, PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('productPrice');

        $query = $qb
            ->select($qb->expr()->count('productPrice.id'))
            ->where($qb->expr()->eq('productPrice.priceList', ':priceList'))
            ->setParameter('priceList', $priceList)
            ->getQuery();

        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);

        return (int)$query
            ->getSingleScalarResult();
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param Product $product
     * @return ProductPrice[]
     */
    public function getPricesByProduct(QueryHintResolverInterface $hintResolver, Product $product)
    {
        $priceLists = $this->getPriceListIdsByProduct($product);

        $prices = [];
        $qb = $this->createQueryBuilder('price');
        $qb->andWhere('price.priceList = :priceList')
            ->andWhere('price.product = :product')
            ->addOrderBy($qb->expr()->asc('price.priceList'))
            ->addOrderBy($qb->expr()->asc('price.unit'))
            ->addOrderBy($qb->expr()->asc('price.currency'))
            ->addOrderBy($qb->expr()->asc('price.quantity'))
            ->setParameter('product', $product);

        foreach ($priceLists as $priceListId) {
            $qb->setParameter('priceList', $priceListId);
            $query = $qb->getQuery();
            $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);
            $pricesByPriceList = $query->getResult();
            $prices = array_merge($prices, $pricesByPriceList);
        }

        return $prices;
    }

    /**
     * Return product prices for specified price list and product IDs
     *
     * @param QueryHintResolverInterface $hintResolver
     * @param int $priceListId
     * @param array $productIds
     * @param bool $getTierPrices
     * @param string|null $currency
     * @param string|null $productUnitCode
     * @param array $orderBy
     * @return ProductPrice[]
     */
    public function findByPriceListIdAndProductIds(
        QueryHintResolverInterface $hintResolver,
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
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);

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
    protected function getFindByPriceListIdAndProductIdsQueryBuilder(
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
            $qb->addOrderBy('price.' . $fieldName, $orderDirection);
        }

        return $qb;
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param int $priceListId
     * @param array $productIds
     * @param array $productUnitCodes
     * @param array $currencies
     * @return array
     */
    public function getPricesBatch(
        QueryHintResolverInterface $hintResolver,
        $priceListId,
        array $productIds,
        array $productUnitCodes,
        array $currencies = []
    ) {
        if (!$productIds || !$productUnitCodes) {
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
                $qb->expr()->in('IDENTITY(price.product)', ':productIds'),
                $qb->expr()->in('IDENTITY(price.unit)', ':productUnitCodes')
            )
            ->setParameter('priceListId', $priceListId)
            ->setParameter('productIds', $productIds)
            ->setParameter('productUnitCodes', $productUnitCodes)
            ->addOrderBy('IDENTITY(price.unit)')
            ->addOrderBy('price.quantity');

        if ($currencies) {
            $qb
                ->andWhere($qb->expr()->in('price.currency', ':currencies'))
                ->setParameter('currencies', $currencies);
        }

        $query = $qb->getQuery();
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);

        return $query->getArrayResult();
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param BasePriceList $priceList
     * @param Product $product
     * @param string|null $currency
     * @return ProductUnit[]
     */
    public function getProductUnitsByPriceList(
        QueryHintResolverInterface $hintResolver,
        BasePriceList $priceList,
        Product $product,
        $currency = null
    ) {
        $qb = $this->getProductUnitsByPriceListQueryBuilder($priceList, $product, $currency);
        $query = $qb->getQuery();
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);

        return $query->getResult();
    }

    /**
     * @param BasePriceList $priceList
     * @param Product $product
     * @param string|null $currency
     *
     * @return QueryBuilder
     */
    protected function getProductUnitsByPriceListQueryBuilder(
        BasePriceList $priceList,
        Product $product,
        $currency = null
    ) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('partial unit.{code}')
            ->from('OroProductBundle:ProductUnit', 'unit')
            ->join($this->_entityName, 'price', Join::WITH, 'price.unit = unit')
            ->where($qb->expr()->eq('price.product', ':product'))
            ->andWhere($qb->expr()->eq('price.priceList', ':priceList'))
            ->setParameter('product', $product)
            ->setParameter('priceList', $priceList)
            ->addOrderBy('unit.code')
            ->groupBy('unit.code');

        if ($currency) {
            $qb->andWhere($qb->expr()->eq('price.currency', ':currency'))
                ->setParameter('currency', $currency);
        }

        return $qb;
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param BasePriceList $priceList
     * @param Collection $products
     * @param string $currency
     * @return array
     */
    public function getProductsUnitsByPriceList(
        QueryHintResolverInterface $hintResolver,
        BasePriceList $priceList,
        Collection $products,
        $currency
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT IDENTITY(price.product) AS productId, unit.code AS code')
            ->from('OroProductBundle:ProductUnit', 'unit')
            ->join($this->_entityName, 'price', Join::WITH, 'price.unit = unit')
            ->where($qb->expr()->in('price.product', ':products'))
            ->andWhere($qb->expr()->eq('price.priceList', ':priceList'))
            ->andWhere($qb->expr()->eq('price.currency', ':currency'))
            ->addOrderBy('unit.code')
            ->setParameters([
                'products' => $products,
                'priceList' => $priceList,
                'currency' => $currency,
            ]);
        $query = $qb->getQuery();
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);
        $productsUnits = $query->getResult();

        $result = [];
        foreach ($productsUnits as $unit) {
            $result[$unit['productId']][] = $unit['code'];
        }

        return $result;
    }

    /**
     * @param BasePriceList $sourcePriceList
     * @param BasePriceList $targetPriceList
     * @param InsertFromSelectShardQueryExecutor $insertQueryExecutor
     */
    public function copyPrices(
        BasePriceList $sourcePriceList,
        BasePriceList $targetPriceList,
        InsertFromSelectShardQueryExecutor $insertQueryExecutor
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
     * @param QueryHintResolverInterface $hintResolver
     * @param BasePriceList $priceList
     * @param array $criteria
     * @param array $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array|BaseProductPrice[]
     */
    public function findByPriceList(
        QueryHintResolverInterface $hintResolver,
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
            $qb->addOrderBy('prices.'.$field, $order);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        $query = $qb->getQuery();
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);

        return $query->getResult();
    }


    /**
     * @param Product $product
     * @return array|int[]
     */
    private function getPriceListIdsByProduct(Product $product)
    {
        $qb = $this->_em->createQueryBuilder();

        $result = $qb->select('IDENTITY(productToPriceList.priceList) as priceListId')
            ->from(PriceListToProduct::class, 'productToPriceList')
            ->where('productToPriceList.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }
}
