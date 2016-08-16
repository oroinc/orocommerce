<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Model\PriceRuleChangeTriggerHandler;

class PriceRuleEntityListener
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var PriceRuleChangeTriggerHandler
     */
    protected $priceRuleChangeTriggerHandler;

    /**
     * @param Cache $cache
     * @param PriceRuleChangeTriggerHandler $priceRuleChangeTriggerHandler
     */
    public function __construct(Cache $cache, PriceRuleChangeTriggerHandler $priceRuleChangeTriggerHandler)
    {
        $this->cache = $cache;
        $this->priceRuleChangeTriggerHandler = $priceRuleChangeTriggerHandler;
    }

    /**
     * Recalculate price rules on price rule change.
     *
     * @param PriceRule $priceRule
     */
    public function postPersist(PriceRule $priceRule)
    {
        $this->priceRuleChangeTriggerHandler->addTriggersForPriceList($priceRule->getPriceList());
    }

    /**
     * Recalculate price rules on price rule change.
     *
     * @param PriceRule $priceRule
     */
    public function preUpdate(PriceRule $priceRule)
    {
        $this->clearCache($priceRule);
        $this->priceRuleChangeTriggerHandler->addTriggersForPriceList($priceRule->getPriceList());
    }

    /**
     * Recalculate price rules on price rule remove.
     *
     * @param PriceRule $priceRule
     */
    public function preRemove(PriceRule $priceRule)
    {
        $this->clearCache($priceRule);
        $this->priceRuleChangeTriggerHandler->addTriggersForPriceList($priceRule->getPriceList());
    }

    /**
     * @param PriceRule $priceRule
     */
    protected function clearCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }
}
