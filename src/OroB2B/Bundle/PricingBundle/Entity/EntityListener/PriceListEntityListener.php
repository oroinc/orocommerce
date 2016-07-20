<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;

class PriceListEntityListener
{
    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param PriceListChangeTriggerHandler $triggerHandler
     * @param Cache $cache
     */
    public function __construct(PriceListChangeTriggerHandler $triggerHandler, Cache $cache)
    {
        $this->triggerHandler = $triggerHandler;
        $this->cache = $cache;
    }

    /**
     * @param PriceList $priceList
     */
    public function postUpdate(PriceList $priceList)
    {
        $this->clearCache($priceList);
    }

    /**
     * @param PriceList $priceList
     */
    public function preRemove(PriceList $priceList)
    {
        $this->clearCache($priceList);
        $this->triggerHandler->handleFullRebuild();
    }

    /**
     * @param PriceList $priceList
     */
    protected function clearCache(PriceList $priceList)
    {
        $this->cache->delete('ar_' . $priceList->getId());
    }
}
