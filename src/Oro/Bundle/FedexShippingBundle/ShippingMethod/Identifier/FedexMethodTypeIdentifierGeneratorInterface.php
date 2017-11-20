<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;

interface FedexMethodTypeIdentifierGeneratorInterface
{
    /**
     * @param FedexShippingService $service
     *
     * @return string
     */
    public function generate(FedexShippingService $service): string;
}
