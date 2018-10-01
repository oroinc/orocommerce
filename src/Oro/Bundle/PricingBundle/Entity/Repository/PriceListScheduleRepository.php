<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;

/**
 * Repository for entity PriceListSchedule
 */
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
                'OroPricingBundle:CombinedPriceListToPriceList',
                'priceListRelations',
                Join::WITH,
                $qb->expr()->eq('schedule.priceList', 'priceListRelations.priceList')
            )
            ->where($qb->expr()->eq('priceListRelations.combinedPriceList', ':cpl'))
            ->setParameter('cpl', $cpl);

        return $qb->getQuery()->getResult();
    }
}
