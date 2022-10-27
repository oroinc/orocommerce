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

    public function __construct(CombinedPriceListActivationPlanBuilder $activationPlanBuilder)
    {
        $this->activationPlanBuilder = $activationPlanBuilder;
    }

    public function onCreate(CombinedPriceListCreateEvent $event)
    {
        // Skip CPLs that consists of Price Lists sub-chains that are not connected to any entity
        // Example: Full chain 1,2,3. There is no 1,2 chain associated to any entity but it is active at the moment and
        // CPL for it was created.
        // Such chain (1,2) should not have it`s own activation plan, because sub-chains will be never assigned
        if (empty($event->getOptions()[CombinedPriceListActivationPlanBuilder::SKIP_ACTIVATION_PLAN_BUILD])) {
            $this->activationPlanBuilder->buildByCombinedPriceList($event->getCombinedPriceList());
        }
    }
}
