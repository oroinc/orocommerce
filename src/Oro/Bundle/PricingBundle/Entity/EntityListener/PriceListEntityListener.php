<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleChangeTriggerHandler;

class PriceListEntityListener
{
    const FIELD_PRODUCT_ASSIGNMENT_RULES = 'productAssignmentRule';

    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var PriceRuleChangeTriggerHandler
     */
    protected $priceRuleChangeTriggerHandler;

    /**
     * @param PriceListChangeTriggerHandler $triggerHandler
     * @param Cache $cache
     * @param PriceRuleChangeTriggerHandler $priceRuleChangeTriggerHandler
     */
    public function __construct(
        PriceListChangeTriggerHandler $triggerHandler,
        Cache $cache,
        PriceRuleChangeTriggerHandler $priceRuleChangeTriggerHandler
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
            $this->priceRuleChangeTriggerHandler->addTriggersForPriceList($priceList);
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
