<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment;

use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Event for collecting Price list collections by Website
 */
class CollectByWebsiteEvent extends CollectByConfigEvent
{
    public const NAME = 'oro_pricing.combined_price_list.assignment.collect.by_website';
    private Website $website;

    public function __construct(Website $website, bool $includeSelfFallback = false, bool $collectOnCurrentLevel = true)
    {
        parent::__construct($includeSelfFallback, $collectOnCurrentLevel);
        $this->website = $website;
    }

    public function getWebsite(): Website
    {
        return $this->website;
    }

    public function addWebsiteAssociation(array $collectionInfo, Website $website): void
    {
        $this->addAssociation($collectionInfo, ['website' => ['ids' => [$website->getId()]]]);
    }
}
