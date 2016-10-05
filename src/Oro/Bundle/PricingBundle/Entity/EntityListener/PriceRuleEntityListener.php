<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;

class PriceRuleEntityListener
{
    const FIELD_QUANTITY = 'quantity';
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
    public function __construct(Cache $cache, PriceListTriggerHandler $priceListTriggerHandler)
    {
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
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
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PriceRule $priceRule, PreUpdateEventArgs $event)
    {
        if (!$this->hasValuableChanges($event)) {
            return;
        }

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
