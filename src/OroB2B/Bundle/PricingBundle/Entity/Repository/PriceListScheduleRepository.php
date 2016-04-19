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
        $qb = $this->createQueryBuilder('shedule')
                ->join('shedule.priceList', 'priceList');
        $qb->innerJoin(
            'OroB2BPricingBundle:CombinedPriceListToPriceList',
            'priceListRelations',
            Join::WITH,
            $qb->expr()->eq('priceList', 'priceListRelations.combinedPriceList')
        )
            ->where($qb->expr()->eq('priceListRelations.combinedPriceList', ':cpl'))
            ->setParameter('cpl', $cpl);

        return $qb->getQuery()->getResult();
    }
}
