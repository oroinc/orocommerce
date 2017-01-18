<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Generator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class MoneyOrderConfigIdentifierGenerator implements IntegrationMethodIdentifierGeneratorInterface
{
    /**
     * @param Channel $channel
     *
     * @return string
     */
    public function generateIdentifier(Channel $channel)
    {
        return MoneyOrder::TYPE . '_' . $channel->getId();
    }
}
