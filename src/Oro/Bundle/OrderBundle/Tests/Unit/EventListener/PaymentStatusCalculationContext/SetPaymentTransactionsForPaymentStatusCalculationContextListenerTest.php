<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\PaymentStatusCalculationContext;

// phpcs:disable

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\PaymentStatusCalculationContext\SetPaymentTransactionsForPaymentStatusCalculationContextListener;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusCalculationContextCollectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// phpcs:enable

final class SetPaymentTransactionsForPaymentStatusCalculationContextListenerTest extends TestCase
{
    private PaymentTransactionProvider&MockObject $paymentTransactionProvider;
    private SetPaymentTransactionsForPaymentStatusCalculationContextListener $listener;

    protected function setUp(): void
    {
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->listener = new SetPaymentTransactionsForPaymentStatusCalculationContextListener(
            $this->paymentTransactionProvider
        );
    }

    public function testOnPaymentStatusCalculationContextCollectWithOrderWithSubOrders(): void
    {
        $parentOrder = new Order();
        ReflectionUtil::setId($parentOrder, 1);

        $subOrder1 = new Order();
        ReflectionUtil::setId($subOrder1, 2);

        $subOrder2 = new Order();
        ReflectionUtil::setId($subOrder2, 3);

        $parentOrder->addSubOrder($subOrder1);
        $parentOrder->addSubOrder($subOrder2);

        $transaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $transaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(50.0)
            ->setActive(true)
            ->setSuccessful(true);

        $transaction3 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(25.0)
            ->setActive(true)
            ->setSuccessful(true);

        $event = new PaymentStatusCalculationContextCollectEvent($parentOrder);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('getPaymentTransactions')
            ->willReturnMap([
                [$subOrder1, [], [], null, null, [$transaction1, $transaction2]],
                [$subOrder2, [], [], null, null, [$transaction3]],
            ]);

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');

        self::assertInstanceOf(ArrayCollection::class, $contextTransactions);
        self::assertCount(3, $contextTransactions);
        self::assertSame($transaction1, $contextTransactions[0]);
        self::assertSame($transaction2, $contextTransactions[1]);
        self::assertSame($transaction3, $contextTransactions[2]);
    }

    public function testOnPaymentStatusCalculationContextCollectWithOrderWithoutSubOrders(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 1);

        $event = new PaymentStatusCalculationContextCollectEvent($order);

        $this->paymentTransactionProvider
            ->expects(self::never())
            ->method('getPaymentTransactions');

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');
        self::assertNull($contextTransactions);
    }

    public function testOnPaymentStatusCalculationContextCollectWithNonOrderEntity(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $this->paymentTransactionProvider
            ->expects(self::never())
            ->method('getPaymentTransactions');

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');
        self::assertNull($contextTransactions);
    }

    public function testOnPaymentStatusCalculationContextCollectDoesNotOverrideExistingTransactions(): void
    {
        $parentOrder = new Order();
        ReflectionUtil::setId($parentOrder, 1);

        $subOrder = new Order();
        ReflectionUtil::setId($subOrder, 2);

        $parentOrder->addSubOrder($subOrder);

        $existingTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setAmount(200.0)
            ->setActive(true)
            ->setSuccessful(true);

        $existingTransactions = new ArrayCollection([$existingTransaction]);

        $event = new PaymentStatusCalculationContextCollectEvent($parentOrder);
        $event->setContextItem('paymentTransactions', $existingTransactions);

        $this->paymentTransactionProvider
            ->expects(self::never())
            ->method('getPaymentTransactions');

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');

        self::assertSame($existingTransactions, $contextTransactions);
        self::assertCount(1, $contextTransactions);
        self::assertSame($existingTransaction, $contextTransactions[0]);
    }

    public function testOnPaymentStatusCalculationContextCollectWithEmptySubOrderTransactions(): void
    {
        $parentOrder = new Order();
        ReflectionUtil::setId($parentOrder, 1);

        $subOrder1 = new Order();
        ReflectionUtil::setId($subOrder1, 2);

        $subOrder2 = new Order();
        ReflectionUtil::setId($subOrder2, 3);

        $parentOrder->addSubOrder($subOrder1);
        $parentOrder->addSubOrder($subOrder2);

        $event = new PaymentStatusCalculationContextCollectEvent($parentOrder);

        $this->paymentTransactionProvider
            ->expects(self::exactly(2))
            ->method('getPaymentTransactions')
            ->willReturnMap([
                [$subOrder1, [], [], null, null, []],
                [$subOrder2, [], [], null, null, []],
            ]);

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');

        self::assertInstanceOf(ArrayCollection::class, $contextTransactions);
        self::assertCount(0, $contextTransactions);
    }

    public function testOnPaymentStatusCalculationContextCollectWithMixedSubOrderTransactions(): void
    {
        $parentOrder = new Order();
        ReflectionUtil::setId($parentOrder, 1);

        $subOrder1 = new Order();
        ReflectionUtil::setId($subOrder1, 2);

        $subOrder2 = new Order();
        ReflectionUtil::setId($subOrder2, 3);

        $subOrder3 = new Order();
        ReflectionUtil::setId($subOrder3, 4);

        $parentOrder->addSubOrder($subOrder1);
        $parentOrder->addSubOrder($subOrder2);
        $parentOrder->addSubOrder($subOrder3);

        $transaction1 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $transaction2 = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $event = new PaymentStatusCalculationContextCollectEvent($parentOrder);

        $this->paymentTransactionProvider
            ->expects(self::exactly(3))
            ->method('getPaymentTransactions')
            ->willReturnMap([
                [$subOrder1, [], [], null, null, [$transaction1]],
                [$subOrder2, [], [], null, null, []],
                [$subOrder3, [], [], null, null, [$transaction2]],
            ]);

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');

        self::assertInstanceOf(ArrayCollection::class, $contextTransactions);
        self::assertCount(2, $contextTransactions);
        self::assertSame($transaction1, $contextTransactions[0]);
        self::assertSame($transaction2, $contextTransactions[1]);
    }

    public function testOnPaymentStatusCalculationContextCollectPreservesOtherContextItems(): void
    {
        $parentOrder = new Order();
        ReflectionUtil::setId($parentOrder, 1);

        $subOrder = new Order();
        ReflectionUtil::setId($subOrder, 2);

        $parentOrder->addSubOrder($subOrder);

        $transaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(150.0)
            ->setActive(true)
            ->setSuccessful(true);

        $event = new PaymentStatusCalculationContextCollectEvent($parentOrder);
        $event->setContextItem('total', 150.0);
        $event->setContextItem('currency', 'USD');

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('getPaymentTransactions')
            ->with($subOrder)
            ->willReturn([$transaction]);

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');

        self::assertInstanceOf(ArrayCollection::class, $contextTransactions);
        self::assertCount(1, $contextTransactions);
        self::assertSame($transaction, $contextTransactions[0]);

        // Verify other context items are preserved
        self::assertEquals(150.0, $event->getContextItem('total'));
        self::assertEquals('USD', $event->getContextItem('currency'));
    }
}
