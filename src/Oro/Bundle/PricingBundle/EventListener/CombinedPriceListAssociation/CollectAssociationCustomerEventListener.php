<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerGroupEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByWebsiteEvent;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Add customer CPLs with information about associated customer to an event.
 */
class CollectAssociationCustomerEventListener
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

    /**
     * Collect CPLs info for customers that have empty group
     */
    public function onCollectAssociationsWebsiteLevel(CollectByWebsiteEvent $event): void
    {
        $website = $event->getWebsite();
        $repo = $this->registry->getRepository(PriceListToCustomer::class);
        foreach ($repo->getAllCustomersWithEmptyGroupAndDefaultFallback($website) as $customer) {
            $this->triggerCustomerEvent($customer, $event);
        }

        if ($event->isIncludeSelfFallback()) {
            foreach ($repo->getAllCustomersWithEmptyGroupAndSelfFallback($website) as $customer) {
                $this->triggerCustomerEvent($customer, $event);
            }
        }
    }

    /**
     * Collect CPLs info for customers that have non-empty group
     */
    public function onCollectAssociationsCustomerGroupLevel(
        CollectByCustomerGroupEvent $event
    ): void {
        $website = $event->getWebsite();
        $customerGroup = $event->getCustomerGroup();
        $repo = $this->registry->getRepository(PriceListToCustomer::class);
        foreach ($repo->getCustomerIteratorWithDefaultFallback($customerGroup, $website) as $customer) {
            $this->triggerCustomerEvent($customer, $event);
        }

        if ($event->isIncludeSelfFallback()) {
            foreach ($repo->getCustomerIteratorWithSelfFallback($customerGroup, $website) as $customer) {
                $this->triggerCustomerEvent($customer, $event);
            }
        }
    }

    public function onCollectAssociations(CollectByCustomerEvent $event): void
    {
        if (!$event->isCollectOnCurrentLevel()) {
            return;
        }

        $website = $event->getWebsite();
        $customer = $event->getCustomer();
        $collection = $this->collectionProvider->getPriceListsByCustomer($customer, $website);
        $collectionInfo = $this->combinedPriceListProvider->getCollectionInformation($collection);
        $event->addCustomerAssociation($collectionInfo, $website, $customer);
    }

    private function triggerCustomerEvent(Customer $customer, CollectByWebsiteEvent $event): void
    {
        $customerEvent = new CollectByCustomerEvent(
            $event->getWebsite(),
            $customer,
            $event->isIncludeSelfFallback()
        );
        $this->eventDispatcher->dispatch($customerEvent, $customerEvent::NAME);
        $event->mergeAssociations($customerEvent);
    }
}
