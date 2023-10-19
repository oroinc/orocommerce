<?php

namespace Oro\Bundle\PricingBundle\Migrations\Service;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListActualizeScheduleEvent;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListRelationHelperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Update CPL activation rules for CPLs that contain price lists with schedules but do not have activation rules.
 */
class ActualizeCplActivationRulesMigration
{
    public function __construct(
        private ManagerRegistry $registry,
        private EventDispatcherInterface $eventDispatcher,
        private CombinedPriceListRelationHelperInterface $relationHelper
    ) {
    }

    public function migrate()
    {
        $containsScheduleQb = $this->registry->getRepository(PriceList::class)
            ->createQueryBuilder('pl')
            ->select('pl.id')
            ->innerJoin(CombinedPriceListToPriceList::class, 'cpl2pl', Join::WITH, 'pl.id = cpl2pl.priceList')
            ->where('cpl2pl.combinedPriceList = cpl')
            ->andWhere('pl.containSchedule = :containSchedule')
            ->setMaxResults(1);

        $hasActivationRuleQb = $this->registry->getRepository(CombinedPriceListActivationRule::class)
            ->createQueryBuilder('r')
            ->select('r.id')
            ->where('r.fullChainPriceList = cpl')
            ->setMaxResults(1);

        $qb = $this->registry->getRepository(CombinedPriceList::class)->createQueryBuilder('cpl');
        $qb->where($qb->expr()->exists($containsScheduleQb->getDQL()))
            ->andWhere($qb->expr()->not($qb->expr()->exists($hasActivationRuleQb->getDQL())))
            ->setParameter('containSchedule', true);

        foreach ($qb->getQuery()->toIterable() as $cpl) {
            if (!$this->relationHelper->isFullChainCpl($cpl)) {
                continue;
            }

            $this->eventDispatcher->dispatch(
                new CombinedPriceListActualizeScheduleEvent($cpl),
                CombinedPriceListActualizeScheduleEvent::NAME
            );
        }
    }
}
