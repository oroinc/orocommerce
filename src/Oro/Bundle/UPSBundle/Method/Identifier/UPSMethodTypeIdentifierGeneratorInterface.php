<?php

namespace Oro\Bundle\UPSBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Entity\ShippingService;

interface UPSMethodTypeIdentifierGeneratorInterface
{
    /**
     * @param Channel $channel
     * @param ShippingService $service
     * @return string
     */
    public function generateIdentifier(Channel $channel, ShippingService $service);
}
