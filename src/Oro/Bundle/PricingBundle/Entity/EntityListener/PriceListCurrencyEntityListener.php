<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;

class PriceListCurrencyEntityListener
{
    /**
     * @var PriceListRelationTriggerHandler
     */
    protected $triggerHandler;

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
     * @param PriceListTriggerHandler $priceListTriggerHandler
     */
    public function __construct(
        Cache $cache,
        PriceListTriggerHandler $priceListTriggerHandler
    ) {
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    /**
     * @param PriceListCurrency $priceListCurrency
     */
    public function postPersist(PriceListCurrency $priceListCurrency)
    {
        $this->scheduleRulesRecalculation($priceListCurrency);
    }

    /**
     * @param PriceListCurrency $priceListCurrency
     */
    public function preRemove(PriceListCurrency $priceListCurrency)
    {
        $this->scheduleRulesRecalculation($priceListCurrency);
    }

    /**
     * @param PriceRule $priceRule
     */
    protected function clearPriceRuleCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }

    /**
     * @param PriceListCurrency $priceListCurrency
     */
    protected function scheduleRulesRecalculation(PriceListCurrency $priceListCurrency)
    {
        $priceList = $priceListCurrency->getPriceList();
        if (count($priceList->getPriceRules()) > 0) {
            $priceList->setActual(false);
            foreach ($priceList->getPriceRules() as $priceRule) {
                $this->clearPriceRuleCache($priceRule);
            }
            $this->priceListTriggerHandler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList);
        }
    }
}
