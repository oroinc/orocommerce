<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Api;

use Oro\Bundle\CheckoutBundle\Api\CheckoutPaymentSubresourceProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutPaymentSubresourceProviderTest extends TestCase
{
    private PaymentMethodProviderInterface|MockObject $paymentMethodProvider;
    private CheckoutPaymentSubresourceProvider $subresourceProvider;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

        $this->subresourceProvider = new CheckoutPaymentSubresourceProvider(
            $this->paymentMethodProvider,
            'test'
        );
    }

    public function testIsSupportedPaymentMethodWithSupportedMethod(): void
    {
        $paymentMethod = 'valid_payment_method';

        $this->paymentMethodProvider->expects(self::once())
            ->method('hasPaymentMethod')
            ->with($paymentMethod)
            ->willReturn(true);

        self::assertTrue($this->subresourceProvider->isSupportedPaymentMethod($paymentMethod));
    }

    public function testIsSupportedPaymentMethodWithUnsupportedMethod(): void
    {
        $paymentMethod = 'invalid_payment_method';

        $this->paymentMethodProvider->expects(self::once())
            ->method('hasPaymentMethod')
            ->with($paymentMethod)
            ->willReturn(false);

        self::assertFalse($this->subresourceProvider->isSupportedPaymentMethod($paymentMethod));
    }

    public function testGetCheckoutPaymentSubresourceName(): void
    {
        self::assertSame('test', $this->subresourceProvider->getCheckoutPaymentSubresourceName());
    }
}
