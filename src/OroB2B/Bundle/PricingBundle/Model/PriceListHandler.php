<?php

namespace OroB2B\Bundle\PricingBundle\Model;

class PriceListHandler
{
    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $priceListChangeTriggerHandler;

    /**
     * @param PriceListChangeTriggerHandler $priceListChangeTriggerHandler
     */
    public function __construct(PriceListChangeTriggerHandler $priceListChangeTriggerHandler)
    {
        $this->priceListChangeTriggerHandler = $priceListChangeTriggerHandler;
    }

    public function handleDelete()
    {
        $this->priceListChangeTriggerHandler->handleFullRebuild();
    }
}
