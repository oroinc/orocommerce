<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CombinedPriceListToPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product $product
     * @return \OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList[]
     */
    public function getPriceListsByCombinedAndProduct(CombinedPriceList $combinedPriceList, Product $product)
    {
        $qb = $this->createQueryBuilder('combinedPriceListToPriceList')
            ->addSelect('priceList');

        $qb->innerJoin('combinedPriceListToPriceList.priceList', 'priceList')
            ->innerJoin('priceList.prices', 'prices', Join::WITH, $qb->expr()->eq('prices.product', ':product'))
            ->orderBy('combinedPriceListToPriceList.sortOrder')
            ->where($qb->expr()->eq('combinedPriceListToPriceList.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList)
            ->setParameter('product', $product);

        return $qb->getQuery()->getResult();
    }
}
