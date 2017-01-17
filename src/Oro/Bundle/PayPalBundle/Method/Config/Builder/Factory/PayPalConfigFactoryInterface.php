<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Builder\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalConfigInterface;

interface PayPalConfigFactoryInterface
{
    /**
     * @return PayPalConfigInterface
     */
    public function createPayPalConfigBuilder();
}
