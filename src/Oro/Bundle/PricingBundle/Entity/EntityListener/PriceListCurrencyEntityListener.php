<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceListCurrenciesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;

/**
 * Catches changes in price list currency to make:
 * 1. Update currency lists in dependent combined price lists
 * 2. Actualize price list rules and actuality
 */
class PriceListCurrencyEntityListener implements OptionalListenerInterface, FeatureToggleableInterface
{
    use OptionalListenerTrait;
    use FeatureCheckerHolderTrait;

    /** @var PriceListRelationTriggerHandler */
    protected $triggerHandler;

    /** @var RuleCache */
    protected $cache;

    /** @var PriceListTriggerHandler */
    protected $priceListTriggerHandler;

    public function __construct(
        RuleCache $cache,
        PriceListTriggerHandler $priceListTriggerHandler
    ) {
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    public function postPersist(PriceListCurrency $priceListCurrency)
    {
        if (!$this->enabled) {
            return;
        }

        $this->scheduleCurrencyUpdate($priceListCurrency);
        $this->scheduleRulesRecalculation($priceListCurrency);
    }

    public function preRemove(PriceListCurrency $priceListCurrency)
    {
        if (!$this->enabled) {
            return;
        }

        $this->scheduleCurrencyUpdate($priceListCurrency);
        $this->scheduleRulesRecalculation($priceListCurrency);
    }

    protected function clearPriceRuleCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }

    protected function scheduleRulesRecalculation(PriceListCurrency $priceListCurrency)
    {
        $priceList = $priceListCurrency->getPriceList();
        if (count($priceList->getPriceRules()) > 0) {
            $priceList->setActual(false);
            foreach ($priceList->getPriceRules() as $priceRule) {
                $this->clearPriceRuleCache($priceRule);
            }
            $this->priceListTriggerHandler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList);
        }
    }

    protected function scheduleCurrencyUpdate(PriceListCurrency $priceListCurrency)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $priceList = $priceListCurrency->getPriceList();
        $this->priceListTriggerHandler->handlePriceListTopic(
            ResolveCombinedPriceListCurrenciesTopic::getName(),
            $priceList
        );
    }
}
