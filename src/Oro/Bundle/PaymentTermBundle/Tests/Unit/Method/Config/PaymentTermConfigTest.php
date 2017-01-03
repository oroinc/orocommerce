<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\OroPaymentTermExtension;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfig;

class PaymentTermConfigTest extends AbstractPaymentConfigTestCase
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

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroPaymentTermExtension::ALIAS;
    }
}
