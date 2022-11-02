<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Processing combined price list associations on website level
 */
class ProcessAssociationWebsiteEventListener extends AbstractProcessAssociationEventListener
{
    public function onProcessAssociations(ProcessEvent $event): void
    {
        $associations = $event->getAssociations();
        $ids = $associations['website']['ids'] ?? [];
        if (empty($ids)) {
            return;
        }
        $websites = $this->getEntitiesByIds($ids, Website::class);

        if (!$websites) {
            return;
        }
        foreach ($websites as $website) {
            $this->actualizeActiveCplRelation($event->getCombinedPriceList(), $website, $event->getVersion());
        }

        if (!$event->isSkipUpdateNotification()) {
            $this->eventDispatcher->dispatch(
                new WebsiteCPLUpdateEvent(array_keys($websites)),
                WebsiteCPLUpdateEvent::NAME
            );
        }
    }

    public function onGetAssociatedWebsites(GetAssociatedWebsitesEvent $event): void
    {
        $associations = $event->getAssociations();
        if ($associations) {
            $websites = $this->getWebsitesByAssociations($associations);
        } else {
            $websites = $this->getWebsitesAssociatedWithCombinedPriceList($event->getCombinedPriceList());
        }

        foreach ($websites as $website) {
            $event->addWebsiteAssociation($website);
        }
    }

    private function getWebsitesByAssociations(array $associations): array
    {
        // Collect website ids from association keys containing website id
        $byWebsite = $associations['website'] ?? [];
        $websiteIds = $byWebsite['ids'] ?? [];
        unset($byWebsite['ids']);
        foreach (array_keys($byWebsite) as $websiteAssociationKey) {
            $websiteIds[] = $this->getWebsiteId($websiteAssociationKey);
        }
        $websiteIds = array_unique($websiteIds);

        return $this->getEntitiesByIds($websiteIds, Website::class);
    }

    private function getWebsitesAssociatedWithCombinedPriceList(CombinedPriceList $combinedPriceList): array
    {
        return $this->registry
            ->getRepository(CombinedPriceListToWebsite::class)
            ->getWebsitesByCombinedPriceList($combinedPriceList);
    }
}
