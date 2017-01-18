<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;

interface MoneyOrderConfigFactoryInterface
{
    /**
     * @param Channel $channel
     *
     * @return MoneyOrderConfig
     */
    public function create(Channel $channel);
}
