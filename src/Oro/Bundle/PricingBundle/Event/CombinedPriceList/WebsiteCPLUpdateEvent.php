<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when combined price lists for websites are updated.
 *
 * This event carries the IDs of websites that have had their combined price list
 * assignments changed, allowing listeners to react to website-specific pricing updates.
 */
class WebsiteCPLUpdateEvent extends Event
{
    public const NAME = 'oro_pricing.website.combined_price_list.update';

    /**
     * @var array
     */
    protected $websiteIds;

    public function __construct(array $websiteIds)
    {
        $this->websiteIds = $websiteIds;
    }

    /**
     * @return array
     */
    public function getWebsiteIds()
    {
        return $this->websiteIds;
    }
}
