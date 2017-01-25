<?php

namespace Oro\Bundle\DPDBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DPDBundle\Entity\ShippingService;

interface DPDMethodTypeIdentifierGeneratorInterface
{
    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return string
     */
    public function generateIdentifier(Channel $channel, ShippingService $service);
}
