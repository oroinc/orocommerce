<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\EventListener\PaymentTransactionListener;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentTransactionListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private PaymentStatusManager&MockObject $manager;
    private EntityManager&MockObject $entityManager;
    private PaymentTransactionListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->manager = $this->createMock(PaymentStatusManager::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->listener = new PaymentTransactionListener($this->doctrine, $this->manager);
    }

    public function testOnTransactionComplete(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setEntityClass(Order::class);
        $paymentTransaction->setEntityIdentifier(123);

        $order = new Order();
        $paymentStatus = new PaymentStatus();
        $paymentStatus->setPaymentStatus(PaymentStatuses::PAID_IN_FULL);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getReference')
            ->with(Order::class, 123)
            ->willReturn($order);

        $this->manager
            ->expects(self::once())
            ->method('updatePaymentStatus')
            ->with($order)
            ->willReturn($paymentStatus);

        $event = new TransactionCompleteEvent($paymentTransaction);
        $this->listener->onTransactionComplete($event);
    }

    public function testOnTransactionCompleteWithDifferentEntityType(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(456);

        $entity = new \stdClass();
        $paymentStatus = new PaymentStatus();
        $paymentStatus->setPaymentStatus(PaymentStatuses::PENDING);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('getReference')
            ->with(\stdClass::class, 456)
            ->willReturn($entity);

        $this->manager
            ->expects(self::once())
            ->method('updatePaymentStatus')
            ->with($entity)
            ->willReturn($paymentStatus);

        $event = new TransactionCompleteEvent($paymentTransaction);
        $this->listener->onTransactionComplete($event);
    }
}
