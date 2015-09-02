<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

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
     * @param PriceList $priceList
     */
    public function deleteByPriceList(PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('productPrice');

        $qb
            ->delete()
            ->where($qb->expr()->eq('productPrice.priceList', ':priceList'))
            ->setParameter('priceList', $priceList)
            ->getQuery()
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
     *
     * @return ProductPrice[]
     */
    public function findByPriceListIdAndProductIds(
        $priceListId,
        array $productIds,
        $getTierPrices = true,
        $currency = null,
        $productUnitCode = null
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
            ->setParameter('productIds', $productIds)
            ->addOrderBy('price.unit')
            ->addOrderBy('price.quantity');

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
        $qb->select('product.id, unit.code, price.quantity, price.value, price.currency')
            ->from($this->_entityName, 'price')
            ->innerJoin('price.product', 'product')
            ->innerJoin('price.unit', 'unit')
            ->where(
                $qb->expr()->eq('IDENTITY(price.priceList)', ':priceListId'),
                $qb->expr()->in('product', ':productIds'),
                $qb->expr()->in('unit', ':productUnitCodes')
            )
            ->setParameter('priceListId', $priceListId)
            ->setParameter('productIds', $productIds)
            ->setParameter('productUnitCodes', $productUnitCodes)
            ->addOrderBy('price.unit')
            ->addOrderBy('price.quantity');

        if ($currencies) {
            $qb
                ->andWhere($qb->expr()->in('price.currency', ':currencies'))
                ->setParameter('currencies', $currencies);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @param string|null $currency
     *
     * @return ProductUnit[]
     */
    public function getProductUnitsByPriceList(PriceList $priceList, Product $product, $currency = null)
    {
        $qb = $this->getProductUnitsByPriceListQueryBuilder($priceList, $product, $currency);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @param string|null $currency
     *
     * @return QueryBuilder
     */
    public function getProductUnitsByPriceListQueryBuilder(PriceList $priceList, Product $product, $currency = null)
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
}
