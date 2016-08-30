<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;

class PriceListEntityListener
{
    const FIELD_PRODUCT_ASSIGNMENT_RULES = 'productAssignmentRule';

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
    protected $priceRuleChangeTriggerHandler;

    /**
     * @param PriceListRelationTriggerHandler $triggerHandler
     * @param Cache $cache
     * @param PriceListTriggerHandler $priceRuleChangeTriggerHandler
     */
    public function __construct(
        PriceListRelationTriggerHandler $triggerHandler,
        Cache $cache,
        PriceListTriggerHandler $priceRuleChangeTriggerHandler
    ) {
        $this->triggerHandler = $triggerHandler;
        $this->cache = $cache;
        $this->priceRuleChangeTriggerHandler = $priceRuleChangeTriggerHandler;
    }

    /**
     * Recalculate product assignments and price rules on product assignment rule change.
     *
     * @param PriceList $priceList
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PriceList $priceList, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(self::FIELD_PRODUCT_ASSIGNMENT_RULES)) {
            $this->clearCache($priceList);
            $priceList->setActual(false);
            $this->priceRuleChangeTriggerHandler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList);
        }
    }

    /**
     * Recalculate Combined Price Lists on price list remove
     *
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
