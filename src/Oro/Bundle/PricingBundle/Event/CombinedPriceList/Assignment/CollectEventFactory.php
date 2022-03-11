<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Exception\UnknownTargetEntityException;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Creates CollectCombinedPriceListAssignmentsEvent based on a given parameters.
 */
class CollectEventFactory implements CollectEventFactoryInterface
{
    public function createEvent(
        bool $force = false,
        Website $website = null,
        object $targetEntity = null
    ): CollectByConfigEvent {
        if (!$website) {
            return new CollectByConfigEvent($force);
        }

        if (!$targetEntity) {
            return new CollectByWebsiteEvent($website, $force);
        }

        if ($targetEntity instanceof CustomerGroup) {
            return new CollectByCustomerGroupEvent($website, $targetEntity, $force);
        }

        if ($targetEntity instanceof Customer) {
            return new CollectByCustomerEvent($website, $targetEntity, $force);
        }

        throw new UnknownTargetEntityException('Unsupported target entity given');
    }
}
