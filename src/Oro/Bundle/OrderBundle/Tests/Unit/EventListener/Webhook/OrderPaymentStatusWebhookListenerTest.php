<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Webhook;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\Webhook\OrderPaymentStatusWebhookListener;
use Oro\Bundle\OrderBundle\EventListener\Webhook\OrderPaymentStatusWebhookTopicListener;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\EventListener\AbstractPaymentStatusListener;
use Oro\Bundle\PaymentBundle\Webhook\PaymentStatusWebhookNotifier;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderPaymentStatusWebhookListenerTest extends TestCase
{
    private PaymentStatusWebhookNotifier&MockObject $paymentStatusWebhookNotifier;
    private ManagerRegistry&MockObject $registry;
    private ObjectRepository&MockObject $repository;
    private OrderPaymentStatusWebhookListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentStatusWebhookNotifier = $this->createMock(PaymentStatusWebhookNotifier::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ObjectRepository::class);

        $this->listener = new OrderPaymentStatusWebhookListener(
            $this->paymentStatusWebhookNotifier,
            $this->registry
        );
    }

    public function testOnTransactionCompleteSkipsAlreadyProcessedTransaction(): void
    {
        $transaction = (new PaymentTransaction())
            ->setSuccessful(true)
            ->setActive(false)
            ->setEntityClass(Order::class)
            ->setEntityIdentifier(1)
            ->setTransactionOptions([AbstractPaymentStatusListener::PAYMENT_NOTIFICATION_SENT => true]);

        $this->registry->expects(self::never())
            ->method('getRepository');

        $this->paymentStatusWebhookNotifier->expects(self::never())
            ->method('notify');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($transaction));
    }

    public function testOnTransactionCompleteSkipsFailedTransaction(): void
    {
        $transaction = (new PaymentTransaction())
            ->setSuccessful(false)
            ->setEntityClass(Order::class)
            ->setEntityIdentifier(1);

        $this->registry->expects(self::never())
            ->method('getRepository');

        $this->paymentStatusWebhookNotifier->expects(self::never())
            ->method('notify');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($transaction));
    }

    public function testOnTransactionCompleteSkipsActiveTransaction(): void
    {
        $transaction = (new PaymentTransaction())
            ->setSuccessful(true)
            ->setActive(true)
            ->setEntityClass(Order::class)
            ->setEntityIdentifier(1);

        $this->registry->expects(self::never())
            ->method('getRepository');

        $this->paymentStatusWebhookNotifier->expects(self::never())
            ->method('notify');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($transaction));
    }

    public function testOnTransactionCompleteSkipsNonOrderEntity(): void
    {
        $transaction = (new PaymentTransaction())
            ->setSuccessful(true)
            ->setEntityClass(\stdClass::class)
            ->setEntityIdentifier(1);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(\stdClass::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(new \stdClass());

        $this->paymentStatusWebhookNotifier->expects(self::never())
            ->method('notify');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($transaction));
    }

    public function testOnTransactionCompleteSendsNotification(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 55);
        $order->setTotal(200.0);

        $transaction = (new PaymentTransaction())
            ->setSuccessful(true)
            ->setEntityClass(Order::class)
            ->setEntityIdentifier(55);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('find')
            ->with(55)
            ->willReturn($order);

        $event = new TransactionCompleteEvent($transaction);

        $this->paymentStatusWebhookNotifier->expects(self::once())
            ->method('notify')
            ->with(OrderPaymentStatusWebhookTopicListener::TOPIC, $transaction, 200.0);

        $this->listener->onTransactionComplete($event);
    }
}
