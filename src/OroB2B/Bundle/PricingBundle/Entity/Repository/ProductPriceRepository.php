<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

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
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();
    }
}
