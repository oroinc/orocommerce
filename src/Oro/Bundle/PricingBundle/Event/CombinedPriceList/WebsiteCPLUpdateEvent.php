<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

class WebsiteCPLUpdateEvent extends Event
{
    const NAME = 'oro_pricing.website.combined_price_list.update';

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
