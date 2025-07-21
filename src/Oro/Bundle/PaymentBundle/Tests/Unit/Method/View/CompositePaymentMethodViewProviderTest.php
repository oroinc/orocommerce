<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CompositePaymentMethodViewProviderTest extends TestCase
{
    private CompositePaymentMethodViewProvider $compositeProvider;

    private MockObject&PaymentMethodViewProviderInterface $innerProvider1;

    private MockObject&PaymentMethodViewProviderInterface $innerProvider2;

    protected function setUp(): void
    {
        $this->innerProvider1 = $this->createMock(PaymentMethodViewProviderInterface::class);
        $this->innerProvider2 = $this->createMock(PaymentMethodViewProviderInterface::class);

        $this->compositeProvider = new CompositePaymentMethodViewProvider(
            [$this->innerProvider1, $this->innerProvider2]
        );
    }

    public function testGetPaymentMethodViews(): void
    {
        $paymentMethodIdentifier1 = 'method1';
        $paymentMethodIdentifier2 = 'method2';
        $paymentMethodView1 = $this->createMock(PaymentMethodViewInterface::class);
        $paymentMethodView2 = $this->createMock(PaymentMethodViewInterface::class);

        $this->innerProvider1
            ->method('hasPaymentMethodView')
            ->withConsecutive([$paymentMethodIdentifier2], [$paymentMethodIdentifier1])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->innerProvider1
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier2)
            ->willReturn($paymentMethodView2);

        $this->innerProvider2
            ->method('hasPaymentMethodView')
            ->withConsecutive([$paymentMethodIdentifier2], [$paymentMethodIdentifier1])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->innerProvider2
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier1)
            ->willReturn($paymentMethodView1);

        $result = $this->compositeProvider->getPaymentMethodViews(
            [$paymentMethodIdentifier2, $paymentMethodIdentifier1]
        );

        self::assertCount(2, $result);
        self::assertSame($paymentMethodView2, $result[0]);
        self::assertSame($paymentMethodView1, $result[1]);
    }

    public function testGetPaymentMethodView(): void
    {
        $paymentMethodView = $this->createMock(PaymentMethodViewInterface::class);

        $paymentMethodIdentifier = 'method1';
        $this->innerProvider1
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProvider1
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethodView);

        $result = $this->compositeProvider->getPaymentMethodView($paymentMethodIdentifier);

        self::assertSame($paymentMethodView, $result);
    }

    public function testGetPaymentMethodViewThrowsExceptionWhenViewNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'There is no payment method view for "method1"'
        );

        $paymentMethodIdentifier = 'method1';
        $this->innerProvider1
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->innerProvider2
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->compositeProvider->getPaymentMethodView($paymentMethodIdentifier);
    }

    public function testHasPaymentMethodView(): void
    {
        $paymentMethodIdentifier = 'method1';
        $this->innerProvider1
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProvider1
            ->method('getPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn($this->createMock(PaymentMethodViewInterface::class));

        self::assertTrue($this->compositeProvider->hasPaymentMethodView($paymentMethodIdentifier));
    }

    public function testHasPaymentMethodViewReturnsFalseWhenViewNotFound(): void
    {
        $paymentMethodIdentifier = 'method1';
        $this->innerProvider1
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->innerProvider2
            ->method('hasPaymentMethodView')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        self::assertFalse($this->compositeProvider->hasPaymentMethodView($paymentMethodIdentifier));
    }
}
