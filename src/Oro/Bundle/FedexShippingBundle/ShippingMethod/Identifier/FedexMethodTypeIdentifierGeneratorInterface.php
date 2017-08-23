<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;

interface FedexMethodTypeIdentifierGeneratorInterface
{
    /**
     * @param ShippingService $service
     *
     * @return string
     */
    public function generate(ShippingService $service): string;
}
