<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;

/**
 * Defines the contract for generating FedEx shipping method type identifiers.
 */
interface FedexMethodTypeIdentifierGeneratorInterface
{
    public function generate(FedexShippingService $service): string;
}
