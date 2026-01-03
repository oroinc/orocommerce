<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener\PaymentStatusCalculationContext;

// phpcs:disable

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusCalculationContextCollectEvent;
use Oro\Bundle\PaymentBundle\EventListener\PaymentStatusCalculationContext\SetPaymentTransactionsForPaymentStatusCalculationContextListener;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
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

    public function testOnPaymentStatusCalculationContextCollectAddsTransactions(): void
    {
        $entity = new \stdClass();

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

        $transactions = [$transaction1, $transaction2];

        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('getPaymentTransactions')
            ->with($entity)
            ->willReturn($transactions);

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');

        self::assertInstanceOf(ArrayCollection::class, $contextTransactions);
        self::assertCount(2, $contextTransactions);
        self::assertSame($transaction1, $contextTransactions[0]);
        self::assertSame($transaction2, $contextTransactions[1]);
    }

    public function testOnPaymentStatusCalculationContextCollectWithEmptyTransactions(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('getPaymentTransactions')
            ->with($entity)
            ->willReturn([]);

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');

        self::assertInstanceOf(ArrayCollection::class, $contextTransactions);
        self::assertCount(0, $contextTransactions);
    }

    public function testOnPaymentStatusCalculationContextCollectDoesNotOverrideExistingTransactions(): void
    {
        $entity = new \stdClass();

        $existingTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(25.0)
            ->setActive(true)
            ->setSuccessful(true);

        $existingTransactions = new ArrayCollection([$existingTransaction]);

        $event = new PaymentStatusCalculationContextCollectEvent($entity);
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

    public function testOnPaymentStatusCalculationContextCollectWithMultipleTransactionTypes(): void
    {
        $entity = new \stdClass();

        $captureTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $authorizeTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setAmount(100.0)
            ->setActive(true)
            ->setSuccessful(true);

        $refundTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::REFUND)
            ->setAmount(30.0)
            ->setActive(true)
            ->setSuccessful(true);

        $failedTransaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::CAPTURE)
            ->setAmount(50.0)
            ->setActive(false)
            ->setSuccessful(false);

        $transactions = [$captureTransaction, $authorizeTransaction, $refundTransaction, $failedTransaction];

        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('getPaymentTransactions')
            ->with($entity)
            ->willReturn($transactions);

        $this->listener->onPaymentStatusCalculationContextCollect($event);

        $contextTransactions = $event->getContextItem('paymentTransactions');

        self::assertInstanceOf(ArrayCollection::class, $contextTransactions);
        self::assertCount(4, $contextTransactions);
        self::assertSame($captureTransaction, $contextTransactions[0]);
        self::assertSame($authorizeTransaction, $contextTransactions[1]);
        self::assertSame($refundTransaction, $contextTransactions[2]);
        self::assertSame($failedTransaction, $contextTransactions[3]);
    }

    public function testOnPaymentStatusCalculationContextCollectPreservesOtherContextItems(): void
    {
        $entity = new \stdClass();

        $transaction = (new PaymentTransaction())
            ->setAction(PaymentMethodInterface::INVOICE)
            ->setAmount(150.0)
            ->setActive(true)
            ->setSuccessful(true);

        $transactions = [$transaction];

        $event = new PaymentStatusCalculationContextCollectEvent($entity);
        $event->setContextItem('total', 150.0);
        $event->setContextItem('currency', 'USD');

        $this->paymentTransactionProvider
            ->expects(self::once())
            ->method('getPaymentTransactions')
            ->with($entity)
            ->willReturn($transactions);

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
