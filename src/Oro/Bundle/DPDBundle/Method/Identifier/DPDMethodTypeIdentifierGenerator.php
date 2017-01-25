<?php

namespace Oro\Bundle\DPDBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DPDBundle\Entity\ShippingService;

class DPDMethodTypeIdentifierGenerator implements DPDMethodTypeIdentifierGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateIdentifier(Channel $channel, ShippingService $service)
    {
        return $service->getCode();
    }
}
