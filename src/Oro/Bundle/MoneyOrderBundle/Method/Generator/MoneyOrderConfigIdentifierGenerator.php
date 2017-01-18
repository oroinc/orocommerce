<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Generator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;

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
