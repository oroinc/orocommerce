<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;

class FedexMethodTypeIdentifierGenerator implements FedexMethodTypeIdentifierGeneratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function generate(FedexShippingService $service): string
    {
        return $service->getCode();
    }
}
