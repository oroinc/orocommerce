<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Formatter;

use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PaymentMethodLabelFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CompositePaymentMethodViewProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentMethodViewProvider;

    /**
     * @var PaymentMethodViewInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentMethodView;

    /**
     * @var PaymentMethodLabelFormatter
     */
    protected $formatter;

    protected function setUp(): void
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

    public function testFormatPaymentMethodLabelWithExistingPaymentMethodAndShortLabel()
    {
        $label = 'short_label';
        $paymentMethodConstant = 'payment_method';
        $this->paymentMethodViewProvider
            ->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);

        $this->paymentMethodView
            ->expects($this->never())
            ->method('getLabel');

        $this->paymentMethodView
            ->expects($this->once())
            ->method('getShortLabel')
            ->willReturn($label);

        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodConstant), $label);
    }

    public function testFormatPaymentMethodLabelWithExistingPaymentMethodAndNonShortLabel()
    {
        $label = 'label';
        $paymentMethodConstant = 'payment_method';
        $this->paymentMethodViewProvider
            ->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);

        $this->paymentMethodView
            ->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->paymentMethodView
            ->expects($this->never())
            ->method('getShortLabel');

        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodConstant, false), $label);
    }

    public function testFormatPaymentMethodLabelWithNotExistinigPaymentMethod()
    {
        $paymentMethodNotExistsConstant = 'not_exists_method';

        $this->paymentMethodViewProvider
            ->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethodNotExistsConstant)
            ->willThrowException(new \InvalidArgumentException());

        $this->paymentMethodView
            ->expects($this->never())
            ->method('getLabel');

        $this->paymentMethodView
            ->expects($this->never())
            ->method('getShortLabel');

        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodNotExistsConstant), '');
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
