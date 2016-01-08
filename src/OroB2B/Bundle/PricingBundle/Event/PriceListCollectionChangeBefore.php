<?php

namespace OroB2B\Bundle\PricingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PriceListCollectionChangeBefore extends Event
{
    const NAME = 'orob2b_pricing.price_list_collection.change_before';

    /**
     * @var object|null
     */
    protected $targetEntity;

    /**
     *
     * @param object|null $targetEntity
     */
    public function __construct($targetEntity = null)
    {
        $this->targetEntity = $targetEntity;
    }
}
