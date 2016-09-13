<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Bundle\PricingBundle\Handler\AffectedPriceListsHandler;

class AssignmentRuleListener
{
    /**
     * @var AffectedPriceListsHandler
     */
    protected $affectedPriceListsHandler;

    /**
     * @param AffectedPriceListsHandler $affectedPriceListsHandler
     */
    public function __construct(AffectedPriceListsHandler $affectedPriceListsHandler)
    {
        $this->affectedPriceListsHandler = $affectedPriceListsHandler;
    }

    /**
     * @param AssignmentBuilderBuildEvent $event
     */
    public function onAssignmentRuleBuilderBuild(AssignmentBuilderBuildEvent $event)
    {
        $priceList = $event->getPriceList();
        
        $this->affectedPriceListsHandler->recalculateByPriceList(
            $priceList,
            AffectedPriceListsHandler::FIELD_ASSIGNED_PRODUCTS,
            false
        );
    }
}
