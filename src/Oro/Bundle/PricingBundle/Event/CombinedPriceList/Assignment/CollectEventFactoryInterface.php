<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment;

use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Creates CollectCombinedPriceListAssignmentsEvent based on a given parameters.
 */
interface CollectEventFactoryInterface
{
    public function createEvent(
        bool $force = false,
        Website $website = null,
        object $targetEntity = null
    ): CollectByConfigEvent;
}
