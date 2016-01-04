<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class CombinedPriceListToPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param null|boolean $mergeAllowed
     * @return PriceList[]|null
     */
    public function getPriceListsByCombined(CombinedPriceList $combinedPriceList, $mergeAllowed = null)
    {
        $qb = $this->createQueryBuilder('combinedPriceListToPriceList')
            ->select('priceList')
            ->innerJoin('combinedPriceListToPriceList.priceList', 'priceList')
            ->orderBy('combinedPriceListToPriceList.sortOrder')
            ->where('combinedPriceListToPriceList.combinedPriceList = :combinedPriceList')
            ->setParameter('combinedPriceList', $combinedPriceList);
        if ($mergeAllowed !== null) {
            $qb->andWhere('combinedPriceListToPriceList.mergeAllowed = :mergeAllowed')
                ->setParameter('mergeAllowed', $mergeAllowed);
        }

        return $qb->getQuery()->getResult();
    }
}
