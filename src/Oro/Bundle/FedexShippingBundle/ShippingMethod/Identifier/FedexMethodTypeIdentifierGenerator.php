<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;

/**
 * Generates unique identifiers for FedEx shipping method types.
 *
 * This generator creates method type identifiers based on the FedEx service code,
 * ensuring each shipping service has a unique identifier within the system.
 */
class FedexMethodTypeIdentifierGenerator implements FedexMethodTypeIdentifierGeneratorInterface
{
    #[\Override]
    public function generate(FedexShippingService $service): string
    {
        return $service->getCode();
    }
}
