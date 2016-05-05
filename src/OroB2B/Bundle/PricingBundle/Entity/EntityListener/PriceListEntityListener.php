<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;

class PriceListEntityListener
{
    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @param PriceListChangeTriggerHandler $triggerHandler
     */
    public function __construct(PriceListChangeTriggerHandler $triggerHandler)
    {
        $this->triggerHandler = $triggerHandler;
    }

    public function preRemove()
    {
        $this->triggerHandler->handleFullRebuild();
    }
}
