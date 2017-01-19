<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Generator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;

class MoneyOrderConfigIdentifierGenerator implements IntegrationIdentifierGeneratorInterface
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
