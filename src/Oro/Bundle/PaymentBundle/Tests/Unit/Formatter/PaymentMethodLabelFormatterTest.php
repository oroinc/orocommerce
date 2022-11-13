<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Formatter;

use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PaymentMethodLabelFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CompositePaymentMethodViewProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodViewProvider;

    /** @var PaymentMethodViewInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodView;

    /** @var PaymentMethodLabelFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->paymentMethodViewProvider = $this->createMock(CompositePaymentMethodViewProvider::class);
        $this->paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);

        $this->formatter = new PaymentMethodLabelFormatter($this->paymentMethodViewProvider);
    }

    public function testFormatPaymentMethodLabelWithExistingPaymentMethodAndShortLabel()
    {
        $label = 'short_label';
        $paymentMethodConstant = 'payment_method';
        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);

        $this->paymentMethodView->expects($this->never())
            ->method('getLabel');

        $this->paymentMethodView->expects($this->once())
            ->method('getShortLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->formatter->formatPaymentMethodLabel($paymentMethodConstant));
    }

    public function testFormatPaymentMethodLabelWithExistingPaymentMethodAndNonShortLabel()
    {
        $label = 'label';
        $paymentMethodConstant = 'payment_method';
        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);

        $this->paymentMethodView->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->paymentMethodView->expects($this->never())
            ->method('getShortLabel');

        $this->assertEquals($label, $this->formatter->formatPaymentMethodLabel($paymentMethodConstant, false));
    }

    public function testFormatPaymentMethodLabelWithNotExistingPaymentMethod()
    {
        $paymentMethodNotExistsConstant = 'not_exists_method';

        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethodNotExistsConstant)
            ->willThrowException(new \InvalidArgumentException());

        $this->paymentMethodView->expects($this->never())
            ->method('getLabel');

        $this->paymentMethodView->expects($this->never())
            ->method('getShortLabel');

        $this->assertEquals('', $this->formatter->formatPaymentMethodLabel($paymentMethodNotExistsConstant));
    }

    public function testFormatPaymentMethodAdminLabel()
    {
        $paymentMethod = 'payment_method';
        $paymentMethodAdminLabel = 'Payment Method';
        $expectedResult = 'Payment Method';

        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethod)
            ->willReturn($this->paymentMethodView);

        $this->paymentMethodView->expects($this->once())
            ->method('getAdminLabel')
            ->willReturn($paymentMethodAdminLabel);

        $this->assertEquals($expectedResult, $this->formatter->formatPaymentMethodAdminLabel($paymentMethod));
    }
}
