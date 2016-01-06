<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CombinedPriceListToPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product $product
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListsByCombinedAndProduct(CombinedPriceList $combinedPriceList, Product $product = null)
    {
        $qb = $this->createQueryBuilder('combinedPriceListToPriceList')
            ->addSelect('partial priceList.{id, name}');

        $qb->innerJoin('combinedPriceListToPriceList.priceList', 'priceList');
        if ($product) {
            $qb->innerJoin('priceList.prices', 'prices', Join::WITH, $qb->expr()->eq('prices.product', ':product'))
                ->setParameter('product', $product);
        }
        $qb->orderBy('combinedPriceListToPriceList.sortOrder')
            ->where($qb->expr()->eq('combinedPriceListToPriceList.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList);

        return $qb->getQuery()->getResult();
    }
}
