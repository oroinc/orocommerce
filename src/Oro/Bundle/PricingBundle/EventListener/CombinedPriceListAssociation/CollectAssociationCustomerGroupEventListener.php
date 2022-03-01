<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerGroupEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByWebsiteEvent;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Add customer group CPLs with information about associated customer group to an event.
 */
class CollectAssociationCustomerGroupEventListener
{
    private PriceListCollectionProvider $collectionProvider;
    private CombinedPriceListProvider $combinedPriceListProvider;
    private ManagerRegistry $registry;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        PriceListCollectionProvider $collectionProvider,
        CombinedPriceListProvider $combinedPriceListProvider,
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->collectionProvider = $collectionProvider;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onCollectAssociationsWebsiteLevel(CollectByWebsiteEvent $event): void
    {
        $website = $event->getWebsite();
        $processedCustomerGroupIds = [];
        $repo = $this->registry->getRepository(PriceListToCustomerGroup::class);
        foreach ($repo->getCustomerGroupIteratorWithDefaultFallback($website) as $customerGroup) {
            $this->triggerCustomerGroupEvent($customerGroup, $event);
            $processedCustomerGroupIds[] = $customerGroup->getId();
        }

        // When force update (or update all) is triggered - include groups with self fallback for a given website
        if ($event->isIncludeSelfFallback()) {
            foreach ($repo->getCustomerGroupIteratorWithSelfFallback($website) as $customerGroup) {
                $this->triggerCustomerGroupEvent($customerGroup, $event);
                $processedCustomerGroupIds[] = $customerGroup->getId();
            }
        }

        /** @var CustomerGroupRepository $customerGroupRepo */
        $customerGroupRepo = $this->registry->getRepository(CustomerGroup::class);
        $unprocessedCustomerGroups = $customerGroupRepo->getCustomerGroupsNotInList($processedCustomerGroupIds);
        // Trigger collect event for customer groups without price list assigned
        foreach ($unprocessedCustomerGroups as $unprocessedCustomerGroup) {
            $this->triggerCustomerGroupEvent($unprocessedCustomerGroup, $event, false);
        }
    }

    public function onCollectAssociations(CollectByCustomerGroupEvent $event): void
    {
        if (!$event->isCollectOnCurrentLevel()) {
            return;
        }

        $website = $event->getWebsite();
        $customerGroup = $event->getCustomerGroup();
        $collection = $this->collectionProvider->getPriceListsByCustomerGroup($customerGroup, $website);
        $collectionInfo = $this->combinedPriceListProvider->getCollectionInformation($collection);
        $event->addCustomerGroupAssociation($collectionInfo, $website, $customerGroup);
    }

    private function triggerCustomerGroupEvent(
        CustomerGroup $customerGroup,
        CollectByWebsiteEvent $event,
        bool $collectOnCurrentLevel = true
    ): void {
        $customerGroupEvent = new CollectByCustomerGroupEvent(
            $event->getWebsite(),
            $customerGroup,
            $event->isIncludeSelfFallback(),
            $collectOnCurrentLevel
        );
        $this->eventDispatcher->dispatch($customerGroupEvent, $customerGroupEvent::NAME);
        $event->mergeAssociations($customerGroupEvent);
    }
}
