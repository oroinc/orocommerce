<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigWithCountryAndCurrencyTest;

class MoneyOrderConfigTest extends AbstractPaymentConfigWithCountryAndCurrencyTest
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
}
