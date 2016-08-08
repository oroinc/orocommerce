<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckoutView;

use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PayflowExpressCheckoutViewTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var PaymentMethodViewInterface */
    protected $methodView;

    /** @var PayflowExpressCheckoutConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentConfig;

    protected function setUp()
    {
        $this->paymentConfig =
            $this->getMock('Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface');

        $this->methodView = $this->createMethodView();
    }

    protected function tearDown()
    {
        unset($this->paymentConfig, $this->methodView);
    }

    public function testGetOptions()
    {
        $this->assertEmpty($this->methodView->getOptions());
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_payflow_express_checkout_widget', $this->methodView->getBlock());
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
        $this->assertEquals('payflow_express_checkout', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $label = 'Label';

        $this->paymentConfig->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->assertSame($label, $this->methodView->getLabel());
    }

    public function testGetShortLabel()
    {
        $shortLAbel = 'Short Label';

        $this->paymentConfig->expects($this->once())
            ->method('getShortLabel')
            ->willReturn($shortLAbel);

        $this->assertSame($shortLAbel, $this->methodView->getShortLabel());
    }

    /**
     * @return PaymentMethodViewInterface
     */
    protected function createMethodView()
    {
        return new PayflowExpressCheckoutView($this->paymentConfig);
    }
}
