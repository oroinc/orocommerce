<?php

namespace Oro\Bundle\UPSBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Entity\ShippingService;

/**
 * Generates unique identifiers for UPS shipping method types.
 *
 * This implementation uses the UPS service code as the unique identifier for each shipping method type.
 * The service code is a standardized UPS identifier that uniquely represents each shipping service
 * (e.g., "03" for Ground, "01" for Next Day Air).
 *
 * @see UPSMethodTypeIdentifierGeneratorInterface
 */
class UPSMethodTypeIdentifierGenerator implements UPSMethodTypeIdentifierGeneratorInterface
{
    #[\Override]
    public function generateIdentifier(Channel $channel, ShippingService $service)
    {
        return $service->getCode();
    }
}
