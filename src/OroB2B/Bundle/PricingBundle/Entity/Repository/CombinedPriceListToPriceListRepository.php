<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;

class CombinedPriceListToPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListsByCombined(CombinedPriceList $combinedPriceList)
    {
        return $this->createQueryBuilder('combinedPriceListToPriceList')
            ->addSelect('priceList')
            ->innerJoin('combinedPriceListToPriceList.priceList', 'priceList')
            ->orderBy('combinedPriceListToPriceList.sortOrder')
            ->where('combinedPriceListToPriceList.combinedPriceList = :combinedPriceList')
            ->setParameter('combinedPriceList', $combinedPriceList)
            ->getQuery()
            ->getResult();
    }
}
