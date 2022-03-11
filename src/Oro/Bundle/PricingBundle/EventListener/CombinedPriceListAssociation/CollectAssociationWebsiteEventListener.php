<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByWebsiteEvent;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Add website CPLs with information about associated website to an event.
 */
class CollectAssociationWebsiteEventListener
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

    public function onCollectAssociationsConfigLevel(CollectByConfigEvent $event): void
    {
        $processedWebsiteIds = [];
        $repo = $this->registry->getRepository(PriceListToWebsite::class);
        foreach ($repo->getWebsiteIteratorWithDefaultFallback() as $website) {
            $this->triggerWebsiteEvent($website, $event);
            $processedWebsiteIds[] = $website->getId();
        }

        // When force update (or update all) is triggered - include websites with self fallback
        if ($event->isIncludeSelfFallback()) {
            foreach ($repo->getWebsiteIteratorWithSelfFallback() as $website) {
                $this->triggerWebsiteEvent($website, $event);
                $processedWebsiteIds[] = $website->getId();
            }
        }

        /** @var WebsiteRepository $websiteRepo */
        $websiteRepo = $this->registry->getRepository(Website::class);
        $unprocessedWebsites = $websiteRepo->getWebsitesNotInList($processedWebsiteIds);
        // Trigger collect event for websites without price list assigned
        foreach ($unprocessedWebsites as $websiteWithoutPlAssigned) {
            $this->triggerWebsiteEvent($websiteWithoutPlAssigned, $event, false);
        }
    }

    public function onCollectAssociations(CollectByWebsiteEvent $event): void
    {
        if (!$event->isCollectOnCurrentLevel()) {
            return;
        }

        $website = $event->getWebsite();
        $collection = $this->collectionProvider->getPriceListsByWebsite($website);
        $collectionInfo = $this->combinedPriceListProvider->getCollectionInformation($collection);
        $event->addWebsiteAssociation($collectionInfo, $website);
    }

    private function triggerWebsiteEvent(
        Website $website,
        CollectByConfigEvent $event,
        bool $collectOnCurrentLevel = true
    ): void {
        $websiteEvent = new CollectByWebsiteEvent(
            $website,
            $event->isIncludeSelfFallback(),
            $collectOnCurrentLevel
        );
        $this->eventDispatcher->dispatch($websiteEvent, $websiteEvent::NAME);
        $event->mergeAssociations($websiteEvent);
    }
}
