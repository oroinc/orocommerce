<?php

namespace Oro\Bundle\FlatRateShippingBundle\Method\Identifier;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class FlatRateMethodIdentifierGenerator implements IntegrationMethodIdentifierGeneratorInterface
{
    /**
     * @internal
     */
    const PREFIX = 'flat_rate';

    /**
     * {@inheritdoc}
     */
    public function generateIdentifier(Channel $channel)
    {
        return self::PREFIX . '_' . $channel->getId();
    }
}
