<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;

class PriceListScheduleRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $cpl
     * @return array PriceListSchedule[]
     */
    public function getSchedulesByCPL(CombinedPriceList $cpl)
    {
        $qb = $this->createQueryBuilder('schedule');
        $qb->select('DISTINCT schedule')
            ->join(
                'OroB2BPricingBundle:CombinedPriceListToPriceList',
                'priceListRelations',
                Join::WITH,
                $qb->expr()->eq('schedule.priceList', 'priceListRelations.priceList')
            )
            ->where($qb->expr()->eq('priceListRelations.combinedPriceList', ':cpl'))
            ->setParameter('cpl', $cpl);

        return $qb->getQuery()->getResult();
    }
}
