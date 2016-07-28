<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

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
     * @var PriceRuleTriggerFiller
     */
    protected $priceRuleTriggersFiller;

    /**
     * @param PriceListChangeTriggerHandler $triggerHandler
     * @param PriceRuleTriggerFiller $priceRuleTriggersFiller
     */
    public function __construct(
        PriceListChangeTriggerHandler $triggerHandler,
        PriceRuleTriggerFiller $priceRuleTriggersFiller
    ) {
        $this->triggerHandler = $triggerHandler;
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
            $this->priceRuleTriggersFiller->addTriggersForPriceList($priceList);
        }
    }

    /**
     * Recalculate Combined Price Lists on price list remove
     */
    public function preRemove()
    {
        $this->triggerHandler->handleFullRebuild();
    }
}
