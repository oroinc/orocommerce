<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;

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
