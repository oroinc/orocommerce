<?php

namespace Oro\Bundle\UPSBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Entity\ShippingService;

class UPSMethodTypeIdentifierGenerator implements UPSMethodTypeIdentifierGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateIdentifier(Channel $channel, ShippingService $service)
    {
        return $service->getCode();
    }
}
