<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfig;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PayflowGatewayConfigTest extends AbstractPayflowGatewayConfigTest
{
    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig(ConfigManager $configManager)
    {
        return new PayflowGatewayConfig($configManager);
    }

    /**
     * @return string
     */
    protected function getConfigPrefix()
    {
        return 'payflow_gateway_';
    }
}
