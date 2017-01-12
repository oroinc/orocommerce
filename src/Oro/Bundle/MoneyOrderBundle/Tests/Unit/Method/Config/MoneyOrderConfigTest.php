<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;

class MoneyOrderConfigTest extends AbstractPaymentConfigTestCase
{
    /**
     * @var MoneyOrderConfigInterface
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig(ConfigManager $configManager)
    {
        return new MoneyOrderConfig($configManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigPrefix()
    {
        return 'money_order_';
    }

    public function testGetPayToKey()
    {
        $returnValue = 'pay_to';
        $this->setConfig($this->once(), Configuration::MONEY_ORDER_PAY_TO_KEY, $returnValue);
        $this->assertSame($returnValue, $this->config->getPayTo());
    }

    public function testGetSendToKey()
    {
        $returnValue = 'send_to';
        $this->setConfig($this->once(), Configuration::MONEY_ORDER_SEND_TO_KEY, $returnValue);
        $this->assertSame($returnValue, $this->config->getSendTo());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroMoneyOrderExtension::ALIAS;
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
