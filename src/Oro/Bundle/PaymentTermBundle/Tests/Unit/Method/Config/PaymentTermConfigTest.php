<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentSystemConfigTestCase;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\OroPaymentTermExtension;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfig;

class PaymentTermConfigTest extends AbstractPaymentSystemConfigTestCase
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

    public function testGetLabel()
    {
        $returnValue = 'test label';
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'label', $returnValue);
        $this->assertSame($returnValue, $this->config->getLabel());
    }

    public function testGetShortLabel()
    {
        $returnValue = 'test short label';
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'short_label', $returnValue);
        $this->assertSame($this->getConfigPrefix() . 'short_label', $this->config->getShortLabel());
    }

    public function testIsRequireCvvEntryEnabled()
    {
        $returnValue = true;
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'require_cvv', $returnValue);
        $this->assertSame($returnValue, $this->config->isRequireCvvEntryEnabled());
    }
}
