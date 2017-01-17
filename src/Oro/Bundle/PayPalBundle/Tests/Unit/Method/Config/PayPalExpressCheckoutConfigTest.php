<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\Config;

use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalExpressCheckoutConfigTest extends AbstractPaymentConfigTestCase
{
    use EntityTrait;

    /** @var PayPalExpressCheckoutConfigInterface */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig()
    {
        $bag = [
            PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY =>'test label',
            PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY => 'test short label',
            PayPalSettings::EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY => 'paypal_payments_pro_express_payment_action',
            PayPalSettings::TEST_MODE_KEY => true,
            PayPalExpressCheckoutConfig::ADMIN_LABEL_KEY => 'test admin label',
            PayPalExpressCheckoutConfig::PAYMENT_METHOD_IDENTIFIER_KEY => 'test_payment_method_identifier'
        ];
        $settingsBag = $this->createMock(ParameterBag::class);
        $settingsBag->expects(static::any())->method('get')->willReturnCallback(
            function () use ($bag) {
                $args = func_get_args();
                return $bag[$args[0]];
            }
        );

        return new PayPalExpressCheckoutConfig($settingsBag);
    }

    public function testIsTestMode()
    {
        $this->assertTrue($this->config->isTestMode());
    }

    public function testGetPurchaseAction()
    {
        $this->assertSame('paypal_payments_pro_express_payment_action', $this->config->getPurchaseAction());
    }
}
