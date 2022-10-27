<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for gathering website associations for a given combined price list.
 */
class GetAssociatedWebsitesEvent extends Event
{
    public const NAME = 'oro_pricing.combined_price_list.assignment.get_associated_websites';

    private CombinedPriceList $combinedPriceList;
    private array $associations;
    /** @var array|Website[] */
    private array $websites = [];

    public function __construct(
        CombinedPriceList $combinedPriceList,
        array $associations = []
    ) {
        $this->associations = $associations;
        $this->combinedPriceList = $combinedPriceList;
    }

    public function getCombinedPriceList(): CombinedPriceList
    {
        return $this->combinedPriceList;
    }

    public function getAssociations(): array
    {
        return $this->associations;
    }

    public function addWebsiteAssociation(Website $website): void
    {
        $this->websites[$website->getId()] = $website;
    }

    public function getWebsites(): array
    {
        return $this->websites;
    }
}
