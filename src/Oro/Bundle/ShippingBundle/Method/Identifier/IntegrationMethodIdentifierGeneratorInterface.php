<?php

namespace Oro\Bundle\ShippingBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface IntegrationMethodIdentifierGeneratorInterface
{
    /**
     * @param Channel $channel
     * @return int|string
     */
    public function generateIdentifier(Channel $channel);
}
