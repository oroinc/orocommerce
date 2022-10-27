<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Provider\PaymentMethodProvider;
use Oro\Bundle\CheckoutBundle\Provider\PaymentResultMessageProvider;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PaymentResultMessageProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodsProvider;

    /** @var PaymentResultMessageProvider */
    protected $messageProvider;

    protected function setUp(): void
    {
        $this->paymentMethodsProvider = $this->createMock(PaymentMethodProvider::class);
        $this->messageProvider = new PaymentResultMessageProvider($this->paymentMethodsProvider);
    }

    public function testGetErrorMessageWithoutTransaction()
    {
        $this->assertEquals(
            'oro.checkout.errors.payment.error_single_method',
            $this->messageProvider->getErrorMessage()
        );
    }

    public function testGetErrorMessageWithoutPaymentMethods()
    {
        $transaction = new PaymentTransaction();
        $this->paymentMethodsProvider->expects($this->once())
            ->method('getApplicablePaymentMethods')
            ->with($transaction)
            ->willReturn(null);

        $this->assertEquals(
            'oro.checkout.errors.payment.error_single_method',
            $this->messageProvider->getErrorMessage($transaction)
        );
    }

    public function testGetErrorMessage()
    {
        $transaction = new PaymentTransaction();
        $this->paymentMethodsProvider->expects($this->once())
            ->method('getApplicablePaymentMethods')
            ->with($transaction)
            ->willReturn([
                $this->createMock(PaymentMethodInterface::class),
                $this->createMock(PaymentMethodInterface::class)
            ]);

        $this->assertEquals(
            'oro.checkout.errors.payment.error_multiple_methods',
            $this->messageProvider->getErrorMessage($transaction)
        );
    }
}
