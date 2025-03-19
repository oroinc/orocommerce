<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Tests\Unit\Stub\PaymentMethodGroupAwareStub;
use PHPUnit\Framework\TestCase;

final class CompositePaymentMethodProviderTest extends TestCase
{
    /** @var iterable<PaymentMethodProviderInterface> */
    private iterable $innerProviders;

    private CompositePaymentMethodProvider $compositeProvider;

    protected function setUp(): void
    {
        $this->innerProviders = [
            $this->createMock(PaymentMethodProviderInterface::class),
            $this->createMock(PaymentMethodProviderInterface::class),
        ];
        $this->compositeProvider = new CompositePaymentMethodProvider($this->innerProviders);
    }

    public function testGetPaymentMethodsWithoutGroup(): void
    {
        $paymentMethodIdentifier1 = 'method1';
        $paymentMethodIdentifier2 = 'method2';
        $paymentMethod1 = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod2 = $this->createMock(PaymentMethodInterface::class);

        $this->innerProviders[0]
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethodIdentifier1 => $paymentMethod1]);

        $this->innerProviders[1]
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethodIdentifier2 => $paymentMethod2]);

        $result = $this->compositeProvider->getPaymentMethods();

        self::assertCount(2, $result);
        self::assertArrayHasKey($paymentMethodIdentifier1, $result);
        self::assertArrayHasKey($paymentMethodIdentifier2, $result);
    }

    public function testGetPaymentMethodsWithGroup(): void
    {
        $this->compositeProvider->setPaymentMethodGroup('group1');

        $paymentMethodIdentifier1 = 'method1';
        $paymentMethodIdentifier2 = 'method2';
        $paymentMethod1 = new PaymentMethodGroupAwareStub($paymentMethodIdentifier1, 'group1');
        $paymentMethod2 = $this->createMock(PaymentMethodInterface::class);

        $this->innerProviders[0]
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethodIdentifier1 => $paymentMethod1]);

        $this->innerProviders[1]
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethodIdentifier2 => $paymentMethod2]);

        $result = $this->compositeProvider->getPaymentMethods();

        self::assertCount(1, $result);
        self::assertArrayHasKey($paymentMethodIdentifier1, $result);
    }

    public function testGetPaymentMethodWithoutGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->innerProviders[0]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethod);

        $result = $this->compositeProvider->getPaymentMethod($paymentMethodIdentifier);

        self::assertSame($paymentMethod, $result);
    }

    public function testGetPaymentMethodWithGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethodGroup = 'group1';
        $paymentMethod = new PaymentMethodGroupAwareStub($paymentMethodIdentifier, $paymentMethodGroup);

        $this->innerProviders[0]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethod);

        $this->compositeProvider->setPaymentMethodGroup($paymentMethodGroup);
        $result = $this->compositeProvider->getPaymentMethod($paymentMethodIdentifier);

        self::assertSame($paymentMethod, $result);
    }

    public function testGetPaymentMethodThrowsExceptionWhenMethodNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'There is no payment method for "method1" identifier that is applicable for "" payment method group.'
        );

        $paymentMethodIdentifier = 'method1';
        $this->innerProviders[0]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->innerProviders[1]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->compositeProvider->getPaymentMethod($paymentMethodIdentifier);
    }

    public function testGetPaymentMethodThrowsExceptionWhenMethodNotApplicableForGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethod = new PaymentMethodGroupAwareStub($paymentMethodIdentifier, 'group2');

        $this->innerProviders[0]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethod);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'There is no payment method for "method1" identifier that is applicable for "group1" payment method group.'
        );

        $this->compositeProvider->setPaymentMethodGroup('group1');
        $this->compositeProvider->getPaymentMethod($paymentMethodIdentifier);
    }

    public function testHasPaymentMethodWithoutGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $this->innerProviders[0]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn($this->createMock(PaymentMethodInterface::class));

        self::assertTrue($this->compositeProvider->hasPaymentMethod($paymentMethodIdentifier));
    }

    public function testHasPaymentMethodWithGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethodGroup = 'group1';
        $paymentMethod = new PaymentMethodGroupAwareStub($paymentMethodIdentifier, $paymentMethodGroup);

        $this->innerProviders[0]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethod);

        $this->compositeProvider->setPaymentMethodGroup($paymentMethodGroup);
        self::assertTrue($this->compositeProvider->hasPaymentMethod($paymentMethodIdentifier));
    }

    public function testHasPaymentMethodReturnsFalseWhenMethodNotApplicableForGroup(): void
    {
        $paymentMethodIdentifier = 'method1';
        $paymentMethod = new PaymentMethodGroupAwareStub($paymentMethodIdentifier, 'group2');

        $this->innerProviders[0]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(true);

        $this->innerProviders[0]
            ->method('getPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn($paymentMethod);

        $this->compositeProvider->setPaymentMethodGroup('group1');
        self::assertFalse($this->compositeProvider->hasPaymentMethod($paymentMethodIdentifier));
    }

    public function testHasPaymentMethodReturnsFalseWhenMethodNotFound(): void
    {
        $paymentMethodIdentifier = 'method1';
        $this->innerProviders[0]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        $this->innerProviders[1]
            ->method('hasPaymentMethod')
            ->with($paymentMethodIdentifier)
            ->willReturn(false);

        self::assertFalse($this->compositeProvider->hasPaymentMethod($paymentMethodIdentifier));
    }
}
