<?php

namespace OroB2B\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Component\EventDispatcher\Event;

class WebsiteCPLUpdateEvent extends Event
{
    const NAME = 'orob2b_pricing.website.combined_price_list.update';

    /**
     * @var array
     */
    protected $websiteIds;

    /**
     * @param array $websiteIds
     */
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
