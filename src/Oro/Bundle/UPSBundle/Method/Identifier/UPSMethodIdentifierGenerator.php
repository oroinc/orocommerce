<?php

namespace Oro\Bundle\UPSBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class UPSMethodIdentifierGenerator implements IntegrationMethodIdentifierGeneratorInterface
{
    const IDENTIFIER = 'ups';

    /**
     * {@inheritdoc}
     */
    public function generateIdentifier(Channel $channel)
    {
        return self::IDENTIFIER.'_'.$channel->getId();
    }
}
