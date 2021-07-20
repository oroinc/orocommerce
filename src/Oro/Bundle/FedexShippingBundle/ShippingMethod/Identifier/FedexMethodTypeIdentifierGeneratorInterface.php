<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;

interface FedexMethodTypeIdentifierGeneratorInterface
{
    public function generate(FedexShippingService $service): string;
}
