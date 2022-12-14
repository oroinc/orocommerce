<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderPaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderPaymentStatusProviderTest extends TestCase
{
    use EntityTrait;

    private PaymentTransactionProvider|MockObject $paymentTransactionProvider;
    private TotalProcessorProvider|MockObject $totalProcessorProvider;
    private OrderPaymentStatusProvider $provider;

    protected function setUp(): void
    {
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);

        $this->provider = new OrderPaymentStatusProvider(
            $this->paymentTransactionProvider,
            $this->totalProcessorProvider
        );
    }

    public function testGetPaymentStatusForSingleOrder()
    {
        $order = $this->getEntity(Order::class, ['id' => 1]);

        $paymentTransaction = $this->getEntity(PaymentTransaction::class);
        $paymentTransaction->setAction(PaymentMethodInterface::AUTHORIZE);
        $paymentTransaction->setAmount(100);
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);

        $subtotal = new Subtotal();
        $subtotal->setAmount(100);
        $this->totalProcessorProvider->expects($this->once())
            ->method('getTotal')
            ->willReturn($subtotal);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransactions')
            ->with($order)
            ->willReturn([$paymentTransaction]);

        $this->assertEquals(PaymentStatusProvider::AUTHORIZED, $this->provider->getPaymentStatus($order));
    }

    public function testGetPaymentStatusForMultiOrder()
    {
        $order = $this->getEntity(Order::class, ['id' => 1]);
        $subOrder1 = $this->getEntity(Order::class, ['id' => 2]);
        $subOrder2 = $this->getEntity(Order::class, ['id' => 3]);
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $paymentTransaction1 = $this->getEntity(PaymentTransaction::class);
        $paymentTransaction1->setAction(PaymentMethodInterface::AUTHORIZE);
        $paymentTransaction1->setAmount(50);
        $paymentTransaction1->setActive(true);
        $paymentTransaction1->setSuccessful(true);

        $paymentTransaction2 = $this->getEntity(PaymentTransaction::class);
        $paymentTransaction2->setAction(PaymentMethodInterface::AUTHORIZE);
        $paymentTransaction2->setAmount(50);
        $paymentTransaction2->setActive(false);
        $paymentTransaction2->setSuccessful(false);

        $subtotal = new Subtotal();
        $subtotal->setAmount(100);
        $this->totalProcessorProvider->expects($this->once())
            ->method('getTotal')
            ->willReturn($subtotal);

        $this->paymentTransactionProvider->expects($this->exactly(2))
            ->method('getPaymentTransactions')
            ->withConsecutive(
                [$subOrder1],
                [$subOrder2]
            )
            ->willReturnOnConsecutiveCalls(
                [$paymentTransaction1],
                [$paymentTransaction2]
            );

        $this->assertEquals(PaymentStatusProvider::AUTHORIZED_PARTIALLY, $this->provider->getPaymentStatus($order));
    }
}
