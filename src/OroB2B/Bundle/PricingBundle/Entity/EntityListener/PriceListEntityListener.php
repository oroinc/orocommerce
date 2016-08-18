<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;
use OroB2B\Bundle\PricingBundle\TriggersFiller\PriceRuleTriggerFiller;

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
     * @var PriceRuleTriggerFiller
     */
    protected $priceRuleTriggersFiller;

    /**
     * @param PriceListChangeTriggerHandler $triggerHandler
     * @param Cache $cache
     * @param PriceRuleTriggerFiller $priceRuleTriggersFiller
     */
    public function __construct(
        PriceListChangeTriggerHandler $triggerHandler,
        Cache $cache,
        PriceRuleTriggerFiller $priceRuleTriggersFiller
    ) {
        $this->triggerHandler = $triggerHandler;
        $this->cache = $cache;
        $this->priceRuleTriggersFiller = $priceRuleTriggersFiller;
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
            $this->priceRuleTriggersFiller->addTriggersForPriceList($priceList);
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
