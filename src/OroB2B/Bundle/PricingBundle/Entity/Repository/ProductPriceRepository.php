<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceRepository extends EntityRepository
{
    /**
     * @param Product $product
     * @param ProductUnit $unit
     */
    public function deleteByProductUnit(Product $product, ProductUnit $unit)
    {
        $qb = $this->createQueryBuilder('productPrice');

        $qb->delete()
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('productPrice.unit', ':unit'),
                    $qb->expr()->eq('productPrice.product', ':product')
                )
            )
            ->setParameter('unit', $unit)
            ->setParameter('product', $product);

        $qb->getQuery()->execute();
    }

    /**
     * @param BasePriceList $priceList
     * @param Product $product
     */
    public function deleteByPriceList(BasePriceList $priceList, Product $product = null)
    {
        $qb = $this->createQueryBuilder('productPrice');

        $qb
            ->delete()
            ->where($qb->expr()->eq('productPrice.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);

        if ($product) {
            $qb->andWhere($qb->expr()->eq('productPrice.product', ':product'))
                ->setParameter('product', $product);
        }

        $qb->getQuery()
            ->execute();
    }

    /**
     * @param PriceList $priceList
     *
     * @return int
     */
    public function countByPriceList(PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('productPrice');

        return (int)$qb
            ->select($qb->expr()->count('productPrice.id'))
            ->where($qb->expr()->eq('productPrice.priceList', ':priceList'))
            ->setParameter('priceList', $priceList)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
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
     * @param Product $product
     * @return ProductPrice[]
     */
    public function getPricesByProduct(Product $product)
    {
        $qb = $this->createQueryBuilder('price');

        return $qb
            ->andWhere('price.product = :product')
            ->addOrderBy($qb->expr()->asc('price.priceList'))
            ->addOrderBy($qb->expr()->asc('price.unit'))
            ->addOrderBy($qb->expr()->asc('price.currency'))
            ->addOrderBy($qb->expr()->asc('price.quantity'))
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();
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
     * @return ProductPrice[]
     */
    public function findByPriceListIdAndProductIds(
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

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $priceListId
     * @param array $productIds
     * @param array $productUnitCodes
     * @param array $currencies
     *
     * @return array
     */
    public function getPricesBatch($priceListId, array $productIds, array $productUnitCodes, array $currencies = [])
    {
        if (!$productIds || !$productUnitCodes) {
            return [];
        }

        $qb = $this->_em->createQueryBuilder();
        $qb->select('product.id, IDENTITY(price.unit) as code, price.quantity, price.value, price.currency')
            ->from($this->_entityName, 'price')
            ->innerJoin('price.product', 'product')
            ->where(
                $qb->expr()->eq('IDENTITY(price.priceList)', ':priceListId'),
                $qb->expr()->in('product', ':productIds'),
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

        return $qb->getQuery()->getResult();
    }

    /**
     * @param BasePriceList $priceList
     * @param Product $product
     * @param string|null $currency
     *
     * @return ProductUnit[]
     */
    public function getProductUnitsByPriceList(BasePriceList $priceList, Product $product, $currency = null)
    {
        $qb = $this->getProductUnitsByPriceListQueryBuilder($priceList, $product, $currency);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param BasePriceList $priceList
     * @param Product $product
     * @param string|null $currency
     *
     * @return QueryBuilder
     */
    public function getProductUnitsByPriceListQueryBuilder(BasePriceList $priceList, Product $product, $currency = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('partial unit.{code}')
            ->from('OroB2BProductBundle:ProductUnit', 'unit')
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
     * @param BasePriceList $priceList
     * @param Collection $products
     * @param string $currency
     *
     * @return array
     */
    public function getProductsUnitsByPriceList(BasePriceList $priceList, Collection $products, $currency)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT IDENTITY(price.product) AS productId, unit.code AS code')
            ->from('OroB2BProductBundle:ProductUnit', 'unit')
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

        $productsUnits = $qb->getQuery()->getResult();

        $result = [];
        foreach ($productsUnits as $unit) {
            $result[$unit['productId']][] = $unit['code'];
        }

        return $result;
    }

    /**
     * @param BasePriceList $sourcePriceList
     * @param BasePriceList $targetPriceList
     * @param InsertFromSelectQueryExecutor $insertQueryExecutor
     */
    public function copyPrices(
        BasePriceList $sourcePriceList,
        BasePriceList $targetPriceList,
        InsertFromSelectQueryExecutor $insertQueryExecutor
    ) {
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
}
