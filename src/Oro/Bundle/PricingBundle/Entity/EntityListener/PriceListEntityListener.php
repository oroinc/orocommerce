<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\AffectedPriceListsHandler;
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
    protected $priceListTriggerHandler;

    /**
     * @var AffectedPriceListsHandler
     */
    protected $affectedPriceListsHandler;

    /**
     * @param PriceListRelationTriggerHandler $triggerHandler
     * @param Cache $cache
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param AffectedPriceListsHandler $affectedPriceListsHandler
     */
    public function __construct(
        PriceListRelationTriggerHandler $triggerHandler,
        Cache $cache,
        PriceListTriggerHandler $priceListTriggerHandler,
        AffectedPriceListsHandler $affectedPriceListsHandler
    ) {
        $this->triggerHandler = $triggerHandler;
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->affectedPriceListsHandler = $affectedPriceListsHandler;
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
            $this->priceListTriggerHandler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList);
            
            $this->affectedPriceListsHandler->recalculateByPriceList(
                $priceList,
                AffectedPriceListsHandler::FIELD_PRODUCT_ASSIGNMENT_RULES,
                false
            );
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
    public function prePersist(PriceList $priceList)
    {
        if ($priceList->getProductAssignmentRule()) {
            $priceList->setActual(false);
            $this->priceListTriggerHandler->addTriggersForPriceList(Topics::CALCULATE_RULE, $priceList);

            $this->affectedPriceListsHandler->recalculateByPriceList(
                $priceList,
                AffectedPriceListsHandler::FIELD_PRODUCT_ASSIGNMENT_RULES,
                false
            );
        }
    }

    /**
     * @param PriceList $priceList
     */
    protected function clearCache(PriceList $priceList)
    {
        $this->cache->delete('ar_' . $priceList->getId());
    }
}
