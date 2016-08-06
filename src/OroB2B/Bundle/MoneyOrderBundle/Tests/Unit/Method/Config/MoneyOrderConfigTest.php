<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;
use OroB2B\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use OroB2B\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigWithCountryAndCurrencyTest;

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
        return OroB2BMoneyOrderExtension::ALIAS;
    }
}
