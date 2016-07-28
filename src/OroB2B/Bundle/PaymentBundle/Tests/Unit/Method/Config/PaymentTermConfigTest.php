<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Method\Config\PaymentTermConfig;

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
