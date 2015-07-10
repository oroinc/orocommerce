<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

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
            ->orderBy('productPrice.currency', Criteria::ASC)
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
        return $this->createQueryBuilder('price')
            ->andWhere('price.product = :product')
            ->addOrderBy('price.priceList', Criteria::ASC)
            ->addOrderBy('price.unit', Criteria::ASC)
            ->addOrderBy('price.currency', Criteria::ASC)
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();
    }

    /**
     * Return product prices for specified price list and product IDs
     *
     * @param int $priceListId
     * @param array $ids
     * @return ProductPrice[]
     */
    public function findByPriceListIdAndProductIds($priceListId, array $ids)
    {
        $queryBuilder = $this->createQueryBuilder('price');
        $queryBuilder
            ->andWhere('IDENTITY(price.priceList) = :priceListId')
            ->andWhere($queryBuilder->expr()->in('IDENTITY(price.product)', $ids))
            ->setParameter('priceListId', $priceListId)
            ->addOrderBy('price.unit')
            ->addOrderBy('price.quantity');

        return $queryBuilder->getQuery()->getResult();
    }
}
