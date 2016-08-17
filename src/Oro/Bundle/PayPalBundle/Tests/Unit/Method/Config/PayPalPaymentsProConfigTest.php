<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProConfig;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PayPalPaymentsProConfigTest extends AbstractPayflowGatewayConfigTest
{
    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig(ConfigManager $configManager)
    {
        return new PayPalPaymentsProConfig($configManager);
    }

    /**
     * @return string
     */
    protected function getConfigPrefix()
    {
        return 'paypal_payments_pro_';
    }
}
