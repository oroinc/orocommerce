<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PayPalBundle\Method\View\PayflowGatewayView;
use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;

class PayflowGatewayViewTest extends AbstractPayflowGatewayViewTest
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /**
     * @return PayflowGatewayView
     */
    protected function getMethodView()
    {
        return new PayflowGatewayView(
            $this->formFactory,
            $this->paymentConfig,
            $this->paymentTransactionProvider
        );
    }

    /** {@inheritdoc} */
    protected function getZeroAmountKey()
    {
        return Configuration::PAYFLOW_GATEWAY_ZERO_AMOUNT_AUTHORIZATION_KEY;
    }

    /** {@inheritdoc} */
    protected function getAllowedCCTypesKey()
    {
        return Configuration::PAYFLOW_GATEWAY_ALLOWED_CC_TYPES_KEY;
    }

    /** {@inheritdoc} */
    protected function getRequireCvvEntryKey()
    {
        return Configuration::PAYFLOW_GATEWAY_REQUIRE_CVV_KEY;
    }

    /** {@inheritdoc} */
    protected function getAuthForRequiredAmountKey()
    {
        return Configuration::PAYFLOW_GATEWAY_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY;
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
        $this->assertEquals('payflow_gateway', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getLabel')
            ->willReturn('label');

        $this->assertEquals('label', $this->methodView->getLabel());
    }

    public function testGetShortLabel()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getShortLabel')
            ->willReturn('short label');

        $this->assertEquals('short label', $this->methodView->getShortLabel());
    }
}
