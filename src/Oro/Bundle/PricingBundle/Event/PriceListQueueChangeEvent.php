<?php

namespace Oro\Bundle\PricingBundle\Event;

use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListQueueChangeEvent extends AbstractPriceListQueueChangeEvent
{
    const BEFORE_CHANGE = 'orob2b_pricing.price_list_collection.change_before';

    /**
     * @var object|null
     */
    protected $targetEntity;

    /**
     * @var Website|null
     */
    protected $website;

    /**
     *
     * @param object|null $targetEntity
     * @param Website $website
     */
    public function __construct($targetEntity = null, Website $website = null)
    {
        $this->targetEntity = $targetEntity;
        $this->website = $website;
    }

    /**
     * @return null|object
     */
    public function getTargetEntity()
    {
        return $this->targetEntity;
    }

    /**
     * @return null|Website
     */
    public function getWebsite()
    {
        return $this->website;
    }
}
