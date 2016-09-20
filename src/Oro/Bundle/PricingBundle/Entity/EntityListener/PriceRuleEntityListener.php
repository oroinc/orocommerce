<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;

class PriceRuleEntityListener
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @param Cache $cache
     * @param PriceListTriggerHandler $priceRuleChangeTriggerHandler
     */
    public function __construct(
        Cache $cache,
        PriceListTriggerHandler $priceRuleChangeTriggerHandler
    ) {
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceRuleChangeTriggerHandler;
    }

    /**
     * Recalculate price rules on price rule change.
     *
     * @param PriceRule $priceRule
     */
    public function postPersist(PriceRule $priceRule)
    {
        $priceList = $priceRule->getPriceList();
        $priceList->setActual(false);

        $this->priceListTriggerHandler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList);
    }

    /**
     * Recalculate price rules on price rule change.
     *
     * @param PriceRule $priceRule
     */
    public function preUpdate(PriceRule $priceRule)
    {
        $priceRule->getPriceList()->setActual(false);
        $this->clearCache($priceRule);
        $priceList = $priceRule->getPriceList();

        $this->priceListTriggerHandler->addTriggerForPriceList(
            Topics::RESOLVE_PRICE_RULES,
            $priceList
        );
    }

    /**
     * Recalculate price rules on price rule remove.
     *
     * @param PriceRule $priceRule
     */
    public function preRemove(PriceRule $priceRule)
    {
        $priceRule->getPriceList()->setActual(false);
        $this->clearCache($priceRule);
        $priceList = $priceRule->getPriceList();
        
        $this->priceListTriggerHandler->addTriggerForPriceList(
            Topics::RESOLVE_PRICE_RULES,
            $priceList
        );
    }

    /**
     * @param PriceRule $priceRule
     */
    protected function clearCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }
}
