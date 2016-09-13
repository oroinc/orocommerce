<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\AffectedPriceListsHandler;
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
     * @var AffectedPriceListsHandler
     */
    protected $affectedPriceListsHandler;

    /**
     * @param Cache $cache
     * @param PriceListTriggerHandler $priceRuleChangeTriggerHandler
     * @param AffectedPriceListsHandler $affectedPriceListsHandler
     */
    public function __construct(
        Cache $cache,
        PriceListTriggerHandler $priceRuleChangeTriggerHandler,
        AffectedPriceListsHandler $affectedPriceListsHandler
    ) {
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceRuleChangeTriggerHandler;
        $this->affectedPriceListsHandler = $affectedPriceListsHandler;
    }

    /**
     * Recalculate price rules on price rule change.
     *
     * @param PriceRule $priceRule
     */
    public function postPersist(PriceRule $priceRule)
    {
        $priceRule->getPriceList()->setActual(false);
        $priceList = $priceRule->getPriceList();

        $this->priceListTriggerHandler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList);

        $this->affectedPriceListsHandler->recalculateByPriceList(
            $priceList,
            AffectedPriceListsHandler::FIELD_PRODUCT_ASSIGNMENT_RULES,
            true
        );
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

        $this->priceListTriggerHandler->addTriggersForPriceList(
            Topics::CALCULATE_RULE,
            $priceList
        );

        $this->affectedPriceListsHandler->recalculateByPriceList(
            $priceList,
            AffectedPriceListsHandler::FIELD_PRODUCT_ASSIGNMENT_RULES,
            true
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
        
        $this->priceListTriggerHandler->addTriggersForPriceList(
            Topics::CALCULATE_RULE,
            $priceList
        );

        $this->affectedPriceListsHandler->recalculateByPriceList(
            $priceList,
            AffectedPriceListsHandler::FIELD_PRODUCT_ASSIGNMENT_RULES,
            true
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
