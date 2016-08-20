<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentTermConfig;

class PaymentTermConfigTest extends AbstractPaymentConfigWithCountryAndCurrencyTest
{
    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig(ConfigManager $configManager)
    {
        return new PaymentTermConfig($configManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigPrefix()
    {
        return 'payment_term_';
    }
}
