<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodGroupAwareProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Tests\Unit\Stub\PaymentMethodGroupAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentMethodGroupAwareProviderTest extends TestCase
{
    private const string PAYMENT_METHOD_GROUP = 'sample_group';

    private PaymentMethodGroupAwareProvider $provider;

    private MockObject&PaymentMethodProviderInterface $innerProvider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->provider = new PaymentMethodGroupAwareProvider($this->innerProvider, self::PAYMENT_METHOD_GROUP);
    }

    public function testDoesNotAddMethodIfNotGroupAware(): void
    {
        $nonGroupAwarePaymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->innerProvider
            ->expects(self::once())
            ->method('getPaymentMethods')
            ->willReturn([$nonGroupAwarePaymentMethod]);

        $result = $this->provider->getPaymentMethods();

        self::assertSame([], $result);
    }

    public function testDoesNotAddMethodIfNotApplicableForGroup(): void
    {
        $paymentMethod = new PaymentMethodGroupAwareStub('payment_method_11', '');

        $this->innerProvider
            ->expects(self::once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        $result = $this->provider->getPaymentMethods();

        self::assertSame([], $result);
    }

    public function testAddsMethodIfApplicable(): void
    {
        $paymentMethod = new PaymentMethodGroupAwareStub('payment_method_11', self::PAYMENT_METHOD_GROUP);

        $this->innerProvider
            ->expects(self::once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        $result = $this->provider->getPaymentMethods();

        self::assertCount(1, $result);
        self::assertSame($paymentMethod, $result['payment_method_11']);
    }

    public function testGetPaymentMethodAndHasPaymentMethod(): void
    {
        $paymentMethod = new PaymentMethodGroupAwareStub('payment_method_11', self::PAYMENT_METHOD_GROUP);

        $this->innerProvider
            ->expects(self::once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        self::assertTrue($this->provider->hasPaymentMethod($paymentMethod->getIdentifier()));
        self::assertSame($paymentMethod, $this->provider->getPaymentMethod($paymentMethod->getIdentifier()));
    }

    public function testGetUnknownMethodReturnsNull(): void
    {
        $this->innerProvider
            ->expects(self::once())
            ->method('getPaymentMethods')
            ->willReturn([]);

        self::assertNull($this->provider->getPaymentMethod('unknown'));
    }

    public function testResetClearsCollectedMethods(): void
    {
        $paymentMethod = new PaymentMethodGroupAwareStub('payment_method_11', self::PAYMENT_METHOD_GROUP);

        $this->innerProvider
            ->expects(self::exactly(2))
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        $this->provider->getPaymentMethods();
        // Reset
        $this->provider->reset();
        // Re-collect after reset
        $paymentMethods = $this->provider->getPaymentMethods();

        self::assertCount(1, $paymentMethods);
        self::assertArrayHasKey($paymentMethod->getIdentifier(), $paymentMethods);
    }

    public function testGetMethodsLazyInitialization(): void
    {
        $paymentMethod = new PaymentMethodGroupAwareStub('payment_method_11', self::PAYMENT_METHOD_GROUP);

        $this->innerProvider
            ->expects(self::once())
            ->method('getPaymentMethods')
            ->willReturn([$paymentMethod]);

        // This should trigger collectMethods()
        $this->provider->getPaymentMethods();
        // This should not trigger it again (no extra getPaymentMethods() call)
        $this->provider->getPaymentMethods();

        // All methods should remain cached
        self::assertTrue($this->provider->hasPaymentMethod($paymentMethod->getIdentifier()));
    }
}
