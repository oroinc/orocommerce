<?php

namespace Oro\Bundle\UPSBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Entity\ShippingService;

/**
 * Defines the contract for generating unique identifiers for UPS shipping method types.
 *
 * Implementations of this interface generate unique string identifiers that distinguish
 * different UPS shipping service types (e.g., Ground, Next Day Air, 2nd Day Air) within a specific integration channel.
 * These identifiers are used throughout the system to reference specific shipping method types.
 */
interface UPSMethodTypeIdentifierGeneratorInterface
{
    /**
     * @param Channel $channel
     * @param ShippingService $service
     * @return string
     */
    public function generateIdentifier(Channel $channel, ShippingService $service);
}
