<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CompositePaymentMethodProviderTest extends TestCase
{
    private MockObject&PaymentMethodProviderInterface $innerProvider1;

    private MockObject&PaymentMethodProviderInterface $innerProvider2;

    private CompositePaymentMethodProvider $compositeProvider;

    protected function setUp(): void
    {
        $this->innerProvider1 = $this->createMock(PaymentMethodProviderInterface::class);
        $this->innerProvider2 = $this->createMock(PaymentMethodProviderInterface::class);

        $this->compositeProvider = new CompositePaymentMethodProvider([$this->innerProvider1, $this->innerProvider2]);
    }

    public function testGetPaymentMethods(): void
    {
        $paymentMethodIdentifier1 = 'method1';
        $paymentMethodIdentifier2 = 'method2';
        $paymentMethod1 = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod2 = $this->createMock(PaymentMethodInterface::class);

        $this->innerProvider1
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethodIdentifier1 => $paymentMethod1]);

        $this->innerProvider2
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethodIdentifier2 => $paymentMethod2]);

        $result = $this->compositeProvider->getPaymentMethods();

        self::assertCount(2, $result);
        self::assertArrayHasKey($paymentMethodIdentifier1, $result);
        self::assertArrayHasKey($paymentMethodIdentifier2, $result);
    }

    public function testGetPaymentMethod(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->innerProvider1
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProvider1
            ->method('getPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethod);

        $result = $this->compositeProvider->getPaymentMethod($paymentMethodIdentifier);

        self::assertSame($paymentMethod, $result);
    }

    public function testGetPaymentMethodThrowsExceptionWhenMethodNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'There is no payment method for "method1" identifier'
        );

        $paymentMethodIdentifier = 'method1';
        $this->innerProvider1
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->innerProvider2
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->compositeProvider->getPaymentMethod($paymentMethodIdentifier);
    }

    public function testHasPaymentMethod(): void
    {
        $paymentMethodIdentifier = 'method1';
        $this->innerProvider1
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProvider1
            ->method('getPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn($this->createMock(PaymentMethodInterface::class));

        self::assertTrue($this->compositeProvider->hasPaymentMethod($paymentMethodIdentifier));
    }

    public function testHasPaymentMethodReturnsFalseWhenMethodNotFound(): void
    {
        $paymentMethodIdentifier = 'method1';
        $this->innerProvider1
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->innerProvider2
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        self::assertFalse($this->compositeProvider->hasPaymentMethod($paymentMethodIdentifier));
    }
}
