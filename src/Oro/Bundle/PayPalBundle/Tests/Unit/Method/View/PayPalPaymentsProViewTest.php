<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProConfig;
use Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProView;
use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;

class PayPalPaymentsProViewTest extends AbstractPayflowGatewayViewTest
{
    /** @var PayPalPaymentsProConfig */
    protected $config;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /**
     * @return PayPalPaymentsProView
     */
    protected function getMethodView()
    {
        return new PayPalPaymentsProView(
            $this->formFactory,
            $this->paymentConfig,
            $this->paymentTransactionProvider
        );
    }

    /** {@inheritdoc} */
    protected function getZeroAmountKey()
    {
        return Configuration::PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY;
    }

    /** {@inheritdoc} */
    protected function getAllowedCCTypesKey()
    {
        return Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY;
    }

    /** {@inheritdoc} */
    protected function getRequireCvvEntryKey()
    {
        return Configuration::PAYPAL_PAYMENTS_PRO_REQUIRE_CVV_KEY;
    }

    /** {@inheritdoc} */
    protected function getAuthForRequiredAmountKey()
    {
        return Configuration::PAYPAL_PAYMENTS_PRO_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY;
    }

    public function testGetOrder()
    {
        $order = '100';

        $this->paymentConfig->expects($this->once())
            ->method('getOrder')
            ->willReturn((int)$order);

        $this->assertSame((int)$order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('paypal_payments_pro', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getLabel')
            ->willReturn('test label');

        $this->assertEquals('test label', $this->methodView->getLabel());
    }

    public function testGetShortLabel()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getShortLabel')
            ->willReturn('test short label');

        $this->assertEquals('test short label', $this->methodView->getShortLabel());
    }
}
