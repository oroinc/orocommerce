<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;

/**
 * Makes CombinedPriceListActivationPlanBuilder generate activation plans based on Price Lists Schedules for the newly
 * created combined price list.
 */
class CombinedPriceListListener
{
    /** @var CombinedPriceListActivationPlanBuilder */
    private $activationPlanBuilder;

    /**
     * @param CombinedPriceListActivationPlanBuilder $activationPlanBuilder
     */
    public function __construct(CombinedPriceListActivationPlanBuilder $activationPlanBuilder)
    {
        $this->activationPlanBuilder = $activationPlanBuilder;
    }

    /**
     * @param CombinedPriceListCreateEvent $event
     */
    public function onCreate(CombinedPriceListCreateEvent $event)
    {
        $this->activationPlanBuilder->buildByCombinedPriceList($event->getCombinedPriceList());
    }
}
