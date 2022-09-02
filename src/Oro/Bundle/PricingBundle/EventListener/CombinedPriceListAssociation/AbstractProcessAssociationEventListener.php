<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Abstract implementation of processing combined price list associations logic.
 */
abstract class AbstractProcessAssociationEventListener
{
    protected EventDispatcherInterface $eventDispatcher;
    protected ManagerRegistry $registry;
    protected CombinedPriceListScheduleResolver $scheduleResolver;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $registry,
        CombinedPriceListScheduleResolver $scheduleResolver
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->registry = $registry;
        $this->scheduleResolver = $scheduleResolver;
    }

    abstract public function onProcessAssociations(ProcessEvent $event): void;

    protected function getWebsite(int $websiteId): ?Website
    {
        return $this->registry->getRepository(Website::class)->find($websiteId);
    }

    protected function getEntitiesByIds(array $ids, string $entityClass): array
    {
        $entities = [];
        $repo = $this->registry->getRepository($entityClass);
        foreach ($ids as $id) {
            $entity = $repo->find($id);
            if ($entity) {
                $entities[$id] = $entity;
            }
        }

        return $entities;
    }

    protected function processAssignments(
        CombinedPriceList $cpl,
        Website $website,
        array $targetEntities
    ): void {
        foreach ($targetEntities as $targetEntity) {
            $this->actualizeActiveCplRelation($cpl, $website, $targetEntity);
        }
    }

    protected function processVersionedAssignments(
        CombinedPriceList $cpl,
        Website $website,
        ?int $version,
        array $targetEntities
    ): void {
        foreach ($targetEntities as $targetEntity) {
            $this->actualizeVersionedActiveCplRelation($cpl, $website, $version, $targetEntity);
        }
    }

    protected function actualizeActiveCplRelation(
        CombinedPriceList $cpl,
        Website $website,
        object $targetEntity = null
    ): BaseCombinedPriceListRelation {
        return $this->actualizeVersionedActiveCplRelation($cpl, $website, null, $targetEntity);
    }

    protected function actualizeVersionedActiveCplRelation(
        CombinedPriceList $cpl,
        Website $website,
        ?int $version,
        object $targetEntity = null
    ): BaseCombinedPriceListRelation {
        $activeCpl = $this->scheduleResolver->getActiveCplByFullCPL($cpl);
        if ($activeCpl === null) {
            $activeCpl = $cpl;
        }

        return $this
            ->getCombinedPriceListRepository()
            ->updateVersionedCombinedPriceListConnection($cpl, $activeCpl, $website, $version, $targetEntity);
    }

    protected function getCombinedPriceListRepository(): CombinedPriceListRepository
    {
        return $this->registry->getRepository(CombinedPriceList::class);
    }

    protected function getWebsiteId(string $key): int
    {
        return (int)str_replace('id:', '', $key);
    }
}
