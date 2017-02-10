<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;

class PaymentMethodLabelFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompositePaymentMethodViewProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewProvider;

    /**
     * @var PaymentMethodViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodView;

    /**
     * @var PaymentMethodLabelFormatter
     */
    protected $formatter;

    public function setUp()
    {
        $this->paymentMethodViewProvider = $this
            ->getMockBuilder(CompositePaymentMethodViewProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodView = $this
            ->getMockBuilder(PaymentMethodViewInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new PaymentMethodLabelFormatter($this->paymentMethodViewProvider);
    }

    public function testFormatPaymentMethodLabel()
    {
        $label = 'label';
        $paymentMethodConstant = 'payment_method';
        $paymentMethodNotExistsConstant = 'not_exists_method';
        $this->paymentMethodViewProvider
            ->expects($this->at(0))
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);
        $this->paymentMethodViewProvider
            ->expects($this->at(1))
            ->method('getPaymentMethodView')
            ->with($paymentMethodNotExistsConstant)
            ->willThrowException(new \InvalidArgumentException());
        $this->paymentMethodViewProvider
            ->expects($this->at(2))
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);

        $this->paymentMethodView
            ->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->paymentMethodView
            ->expects($this->once())
            ->method('getShortLabel')
            ->willReturn($label);

        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodConstant), $label);
        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodNotExistsConstant), '');
        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodConstant, false), $label);
    }

    public function testFormatPaymentMethodAdminLabel()
    {
        $paymentMethod = 'payment_method';
        $paymentMethodAdminLabel = 'Payment Method';
        $expectedResult = 'Payment Method';

        $this->paymentMethodViewProvider
            ->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethod)
            ->willReturn($this->paymentMethodView)
        ;

        $this->paymentMethodView
            ->expects($this->once())
            ->method('getAdminLabel')
            ->willReturn($paymentMethodAdminLabel)
        ;

        $this->assertEquals($this->formatter->formatPaymentMethodAdminLabel($paymentMethod), $expectedResult);
    }
}
