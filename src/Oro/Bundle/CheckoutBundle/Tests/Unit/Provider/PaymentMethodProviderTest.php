<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\PaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider as PaymentBundleMethodProvider;

class PaymentMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentMethodProvider */
    protected $provider;

    /** @var CheckoutPaymentContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutPaymentContextProvider;

    /** @var CheckoutRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var PaymentBundleMethodProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentBundleMethodProvider;

    /** @var PaymentTransaction */
    protected $transaction;

    protected function setUp(): void
    {
        $this->checkoutPaymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);
        $this->repository = $this->createMock(CheckoutRepository::class);
        $this->paymentBundleMethodProvider = $this->createMock(PaymentBundleMethodProvider::class);

        $this->transaction = new PaymentTransaction();

        $this->provider = new PaymentMethodProvider(
            $this->checkoutPaymentContextProvider,
            $this->repository,
            $this->paymentBundleMethodProvider
        );
    }

    public function testGetApplicablePaymentMethodsWithoutCheckoutId()
    {
        $this->transaction->setTransactionOptions(['checkoutId' => null]);
        $this->paymentBundleMethodProvider->expects($this->never())->method('getApplicablePaymentMethods');

        $this->assertNull($this->provider->getApplicablePaymentMethods($this->transaction));
    }

    public function testGetApplicablePaymentMethodsWithoutCheckout()
    {
        $this->transaction->setTransactionOptions(['checkoutId' => 123]);
        $this->repository->expects($this->once())->method('find')->with(123)->willReturn(null);
        $this->paymentBundleMethodProvider->expects($this->never())->method('getApplicablePaymentMethods');

        $this->assertNull($this->provider->getApplicablePaymentMethods($this->transaction));
    }

    public function testGetApplicablePaymentMethodsWithoutContext()
    {
        $this->transaction->setTransactionOptions(['checkoutId' => 123]);
        $checkout = new Checkout();
        $this->repository->expects($this->once())->method('find')->with(123)->willReturn($checkout);
        $this->checkoutPaymentContextProvider->expects($this->once())
            ->method('getContext')->with($checkout)->willReturn(null);
        $this->paymentBundleMethodProvider->expects($this->never())->method('getApplicablePaymentMethods');

        $this->assertNull($this->provider->getApplicablePaymentMethods($this->transaction));
    }

    public function testGetApplicablePaymentMethods()
    {
        $this->transaction->setTransactionOptions(['checkoutId' => 123]);
        $checkout = new Checkout();
        $this->repository->expects($this->once())->method('find')->with(123)->willReturn($checkout);
        $paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->checkoutPaymentContextProvider->expects($this->once())
            ->method('getContext')->with($checkout)->willReturn($paymentContext);
        $paymentMethodInterfaces = [$this->createMock(PaymentMethodInterface::class)];
        $this->paymentBundleMethodProvider->expects($this->once())
            ->method('getApplicablePaymentMethods')
            ->with($paymentContext)
            ->willReturn($paymentMethodInterfaces);

        $this->assertSame($paymentMethodInterfaces, $this->provider->getApplicablePaymentMethods($this->transaction));
    }
}
