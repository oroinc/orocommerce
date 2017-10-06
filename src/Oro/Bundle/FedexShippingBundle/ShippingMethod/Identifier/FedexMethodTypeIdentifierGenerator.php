<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;

class FedexMethodTypeIdentifierGenerator implements FedexMethodTypeIdentifierGeneratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function generate(ShippingService $service): string
    {
        return $service->getCode();
    }
}
