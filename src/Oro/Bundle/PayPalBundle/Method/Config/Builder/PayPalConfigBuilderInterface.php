<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Builder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalConfigInterface;

interface PayPalConfigBuilderInterface
{
    /**
     * @return PayPalConfigInterface
     */
    public function getResult();

    /**
     * @param Channel $channel
     *
     * @return self
     */
    public function setChannel(Channel $channel);
}
