<?php

namespace Oro\Bundle\DPDBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class DPDMethodIdentifierGenerator implements IntegrationMethodIdentifierGeneratorInterface
{
    const IDENTIFIER = 'dpd';

    /**
     * {@inheritdoc}
     */
    public function generateIdentifier(Channel $channel)
    {
        return self::IDENTIFIER.'_'.$channel->getId();
    }
}
