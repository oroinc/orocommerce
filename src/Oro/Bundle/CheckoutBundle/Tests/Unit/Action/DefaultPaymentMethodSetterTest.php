<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\DefaultPaymentMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class DefaultPaymentMethodSetterTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ApplicablePaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject $applicablePaymentMethodsProvider;

    private CheckoutPaymentContextProvider|\PHPUnit\Framework\MockObject\MockObject $checkoutPaymentContextProvider;

    private DefaultPaymentMethodSetter $defaultPaymentMethodSetter;

    protected function setUp(): void
    {
        $this->applicablePaymentMethodsProvider = $this->createMock(ApplicablePaymentMethodsProvider::class);
        $this->checkoutPaymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);

        $this->defaultPaymentMethodSetter = new DefaultPaymentMethodSetter(
            $this->applicablePaymentMethodsProvider,
            $this->checkoutPaymentContextProvider
        );

        $this->setUpLoggerMock($this->defaultPaymentMethodSetter);
    }

    public function testSetDefaultPaymentMethodWhenNoPaymentMethod(): void
    {
        $checkout = new Checkout();
        $paymentMethod = 'sample_method';
        $checkout->setPaymentMethod($paymentMethod);

        $this->applicablePaymentMethodsProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Skipping setting default payment method for checkout because it is already set: {payment_method}',
                ['payment_method' => $paymentMethod, 'checkout' => $checkout]
            );

        $this->defaultPaymentMethodSetter->setDefaultPaymentMethod($checkout);

        self::assertSame($paymentMethod, $checkout->getPaymentMethod());
    }

    public function testSetDefaultPaymentMethodWhenNoPaymentContext(): void
    {
        $checkout = new Checkout();

        $this->checkoutPaymentContextProvider
            ->expects(self::once())
            ->method('getContext')
            ->willReturn(null);

        $this->applicablePaymentMethodsProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Failed to get a payment context, skipping setting default payment method for checkout',
                ['checkout' => $checkout]
            );

        $this->defaultPaymentMethodSetter->setDefaultPaymentMethod($checkout);

        self::assertNull($checkout->getPaymentMethod());
    }

    public function testSetDefaultPaymentMethodWhenNoPaymentMethods(): void
    {
        $checkout = new Checkout();

        $paymentContext = new PaymentContext([]);
        $this->checkoutPaymentContextProvider
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($paymentContext);

        $this->applicablePaymentMethodsProvider
            ->expects(self::once())
            ->method('getApplicablePaymentMethods')
            ->with($paymentContext)
            ->willReturn([]);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Skipping setting default payment method for checkout because there are no applicable payment methods',
                ['checkout' => $checkout]
            );

        $this->defaultPaymentMethodSetter->setDefaultPaymentMethod($checkout);

        self::assertNull($checkout->getPaymentMethod());
    }

    public function testSetDefaultPaymentMethod(): void
    {
        $checkout = new Checkout();

        $paymentContext = new PaymentContext([]);
        $this->checkoutPaymentContextProvider
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($paymentContext);

        $paymentMethod1 = $this->createMock(PaymentMethodInterface::class);
        $paymentMethodIdentifier = 'sample_method1';
        $paymentMethod1
            ->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($paymentMethodIdentifier);

        $paymentMethod2 = $this->createMock(PaymentMethodInterface::class);
        $this->applicablePaymentMethodsProvider
            ->expects(self::once())
            ->method('getApplicablePaymentMethods')
            ->with($paymentContext)
            ->willReturn([$paymentMethod1, $paymentMethod2]);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'The default payment method is set for checkout: {payment_method}',
                ['checkout' => $checkout, 'payment_method' => $paymentMethodIdentifier]
            );

        $this->defaultPaymentMethodSetter->setDefaultPaymentMethod($checkout);

        self::assertSame($paymentMethodIdentifier, $checkout->getPaymentMethod());
    }
}
