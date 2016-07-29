<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;

class PayPalPaymentsProExpressCheckoutConfigTest extends AbstractPaymentConfigTestCase
{
    /** @var PayflowExpressCheckoutConfigInterface */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig(ConfigManager $configManager)
    {
        return new PayPalPaymentsProExpressCheckoutConfig($configManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigPrefix()
    {
        return 'paypal_payments_pro_express_checkout_';
    }

    public function testGetCredentials()
    {
        $expectedValues = [
            'test1',
            'test2',
            'test3',
            'test4',
            Option\Partner::PAYPAL,
            Option\Tender::PAYPAL
        ];

        $this->configManager->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(
                [$this->getConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_PARTNER_KEY)],
                [$this->getConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_VENDOR_KEY)],
                [$this->getConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_USER_KEY)],
                [$this->getConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_PASSWORD_KEY)]
            )->willReturnOnConsecutiveCalls(
                'test1',
                'test2',
                'test3',
                'test4'
            );

        $returnValues = $this->config->getCredentials();
        $this->assertCount(0, array_diff($returnValues, $expectedValues));
    }

    public function testIsTestMode()
    {
        $returnValue = true;
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY, $returnValue);
        $this->assertSame($returnValue, $this->config->isTestMode());
    }

    public function testGetPurchaseAction()
    {
        $returnValue = 'capture';
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'payment_action', $returnValue);
        $this->assertSame($returnValue, $this->config->getPurchaseAction());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroPayPalExtension::ALIAS;
    }
}
