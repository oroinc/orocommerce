<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Formatter;

use Oro\Bundle\PaymentBundle\Event\CollectFormattedPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodOptionsFormatter;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentMethodOptionsFormatterTest extends \PHPUnit\Framework\TestCase
{
    private PaymentMethodViewProviderInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethodViewProvider;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private PaymentMethodOptionsFormatter $formatter;

    protected function setUp(): void
    {
        $this->paymentMethodViewProvider = $this->createMock(PaymentMethodViewProviderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->formatter = new PaymentMethodOptionsFormatter($this->paymentMethodViewProvider, $this->eventDispatcher);
    }

    public function testFormatPaymentMethodOptionsWhenThrowsException(): void
    {
        $paymentMethod = 'test_payment_method';
        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethod)
            ->willThrowException(new \InvalidArgumentException());
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $result = $this->formatter->formatPaymentMethodOptions($paymentMethod);
        self::assertCount(0, $result);
    }

    public function testFormatPaymentMethodOptions(): void
    {
        $paymentMethod = 'test_payment_method';
        $paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);
        $this->paymentMethodViewProvider->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethod)
            ->willReturn($paymentMethodView);
        $option = 'some option';
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(CollectFormattedPaymentOptionsEvent::class),
                CollectFormattedPaymentOptionsEvent::EVENT_NAME
            )
            ->willReturnCallback(
                function (CollectFormattedPaymentOptionsEvent $event) use ($option) {
                    $event->addOption($option);

                    return $event;
                }
            );

        $result = $this->formatter->formatPaymentMethodOptions($paymentMethod);
        self::assertEquals([$option], $result);
    }
}
