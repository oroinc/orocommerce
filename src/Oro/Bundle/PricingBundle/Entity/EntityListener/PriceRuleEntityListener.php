<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;

/**
 * Handles price rule changes, schedule dependent price rule recalculation.
 */
class PriceRuleEntityListener
{
    const FIELD_QUANTITY = 'quantity';

    /** @var RuleCache */
    protected $cache;

    /** @var PriceListTriggerHandler */
    protected $priceListTriggerHandler;

    public function __construct(RuleCache $cache, PriceListTriggerHandler $priceListTriggerHandler)
    {
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    /**
     * Recalculate price rules on price rule change.
     */
    public function postPersist(PriceRule $priceRule)
    {
        $priceList = $priceRule->getPriceList();
        $priceList->setActual(false);

        $this->priceListTriggerHandler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList);
    }

    /**
     * Recalculate price rules on price rule change.
     */
    public function preUpdate(PriceRule $priceRule, PreUpdateEventArgs $event)
    {
        if (!$this->hasValuableChanges($event)) {
            return;
        }

        $priceRule->getPriceList()->setActual(false);
        $this->clearCache($priceRule);
        $priceList = $priceRule->getPriceList();

        $this->priceListTriggerHandler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList);
    }

    /**
     * Recalculate price rules on price rule remove.
     */
    public function preRemove(PriceRule $priceRule)
    {
        $priceRule->getPriceList()->setActual(false);
        $this->clearCache($priceRule);
        $priceList = $priceRule->getPriceList();

        $this->priceListTriggerHandler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList);
    }

    protected function clearCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }

    /**
     * @param PreUpdateEventArgs $event
     * @return bool
     */
    protected function hasValuableChanges(PreUpdateEventArgs $event)
    {
        $changeSet = $event->getEntityChangeSet();

        if (count($changeSet) === 1 && $event->hasChangedField(self::FIELD_QUANTITY)) {
            $oldValue = $event->getOldValue(self::FIELD_QUANTITY);
            $newValue = $event->getNewValue(self::FIELD_QUANTITY);
            if (is_numeric($newValue) && (float)$oldValue === (float)$newValue) {
                return false;
            }
        }

        return true;
    }
}
