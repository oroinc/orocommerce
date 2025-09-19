<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentStatusRepository;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusUpdatedEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculatorInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PaymentStatusManagerTest extends TestCase
{
    private PaymentStatusCalculatorInterface&MockObject $paymentStatusCalculator;
    private DoctrineHelper&MockObject $doctrineHelper;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private PaymentStatusManager $paymentStatusManager;

    protected function setUp(): void
    {
        $this->paymentStatusCalculator = $this->createMock(PaymentStatusCalculatorInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->paymentStatusManager = new PaymentStatusManager(
            $this->paymentStatusCalculator,
            $this->doctrineHelper,
            $this->eventDispatcher
        );
    }

    public function testGetPaymentStatusWhenExists(): void
    {
        $entity = new \stdClass();
        $entityClass = 'App\Entity\Order';
        $entityId = 123;
        $existingPaymentStatus = new PaymentStatus();
        $existingPaymentStatus->setPaymentStatus(PaymentStatuses::PAID_IN_FULL);

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'entityClass' => $entityClass,
                'entityIdentifier' => $entityId,
            ])
            ->willReturn($existingPaymentStatus);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository);

        $this->paymentStatusCalculator
            ->expects(self::never())
            ->method('calculatePaymentStatus');

        $result = $this->paymentStatusManager->getPaymentStatus($entity);

        self::assertSame($existingPaymentStatus, $result);
    }

    public function testGetPaymentStatusWhenNotExists(): void
    {
        $entity = new \stdClass();
        $entityClass = 'App\Entity\Order';
        $entityId = 123;
        $calculatedStatus = PaymentStatuses::PENDING;
        $createdPaymentStatus = new PaymentStatus();
        $createdPaymentStatus->setPaymentStatus($calculatedStatus);
        $targetEntityReference = new \stdClass();

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $paymentStatusRepository = $this->createMock(PaymentStatusRepository::class);
        $paymentStatusRepository
            ->expects(self::once())
            ->method('upsertPaymentStatus')
            ->with($entityClass, $entityId, $calculatedStatus, false)
            ->willReturn($createdPaymentStatus);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository, $paymentStatusRepository);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityReference')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntityReference);

        $this->paymentStatusCalculator
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($entity)
            ->willReturn($calculatedStatus);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(
                    static function (PaymentStatusUpdatedEvent $event) use (
                        $createdPaymentStatus,
                        $targetEntityReference
                    ) {
                        return $event->getPaymentStatus() === $createdPaymentStatus
                            && $event->getTargetEntity() === $targetEntityReference;
                    }
                )
            );

        $result = $this->paymentStatusManager->getPaymentStatus($entity);

        self::assertSame($createdPaymentStatus, $result);
    }

    public function testSetPaymentStatus(): void
    {
        $entity = new \stdClass();
        $entityClass = 'App\Entity\Order';
        $entityId = 123;
        $paymentStatus = PaymentStatuses::PAID_IN_FULL;
        $force = true;
        $createdPaymentStatus = new PaymentStatus();
        $createdPaymentStatus->setPaymentStatus($paymentStatus);
        $targetEntityReference = new \stdClass();

        $paymentStatusRepository = $this->createMock(PaymentStatusRepository::class);
        $paymentStatusRepository
            ->expects(self::once())
            ->method('upsertPaymentStatus')
            ->with($entityClass, $entityId, $paymentStatus, $force)
            ->willReturn($createdPaymentStatus);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($paymentStatusRepository);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityReference')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntityReference);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(
                    static function (PaymentStatusUpdatedEvent $event) use (
                        $createdPaymentStatus,
                        $targetEntityReference
                    ) {
                        return $event->getPaymentStatus() === $createdPaymentStatus
                            && $event->getTargetEntity() === $targetEntityReference;
                    }
                )
            );

        $result = $this->paymentStatusManager->setPaymentStatus($entity, $paymentStatus, $force);

        self::assertSame($createdPaymentStatus, $result);
    }

    public function testUpdatePaymentStatusWhenNotForced(): void
    {
        $entity = new \stdClass();
        $entityClass = 'App\Entity\Order';
        $entityId = 123;
        $calculatedStatus = PaymentStatuses::DECLINED;
        $existingPaymentStatus = new PaymentStatus();
        $existingPaymentStatus->setPaymentStatus(PaymentStatuses::PENDING);
        $existingPaymentStatus->setForced(false);
        $updatedPaymentStatus = new PaymentStatus();
        $updatedPaymentStatus->setPaymentStatus($calculatedStatus);
        $targetEntityReference = new \stdClass();

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'entityClass' => $entityClass,
                'entityIdentifier' => $entityId,
            ])
            ->willReturn($existingPaymentStatus);

        $paymentStatusRepository = $this->createMock(PaymentStatusRepository::class);
        $paymentStatusRepository
            ->expects(self::once())
            ->method('upsertPaymentStatus')
            ->with($entityClass, $entityId, $calculatedStatus, false)
            ->willReturn($updatedPaymentStatus);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository, $paymentStatusRepository);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityReference')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntityReference);

        $this->paymentStatusCalculator
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($entity)
            ->willReturn($calculatedStatus);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(
                    static function (PaymentStatusUpdatedEvent $event) use (
                        $updatedPaymentStatus,
                        $targetEntityReference
                    ) {
                        return $event->getPaymentStatus() === $updatedPaymentStatus
                            && $event->getTargetEntity() === $targetEntityReference;
                    }
                )
            );

        $result = $this->paymentStatusManager->updatePaymentStatus($entity, false);

        self::assertSame($updatedPaymentStatus, $result);
    }

    public function testUpdatePaymentStatusWhenForcedAndNotOverridden(): void
    {
        $entity = new \stdClass();
        $entityClass = 'App\Entity\Order';
        $entityId = 123;
        $existingPaymentStatus = new PaymentStatus();
        $existingPaymentStatus->setPaymentStatus(PaymentStatuses::PAID_IN_FULL);
        $existingPaymentStatus->setForced(true);

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with([
                'entityClass' => $entityClass,
                'entityIdentifier' => $entityId,
            ])
            ->willReturn($existingPaymentStatus);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository);

        $this->paymentStatusCalculator
            ->expects(self::never())
            ->method('calculatePaymentStatus');

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $result = $this->paymentStatusManager->updatePaymentStatus($entity, false);

        self::assertSame($existingPaymentStatus, $result);
    }

    public function testUpdatePaymentStatusWhenForcedButOverridden(): void
    {
        $entity = new \stdClass();
        $entityClass = 'App\Entity\Order';
        $entityId = 123;
        $calculatedStatus = PaymentStatuses::CANCELED;
        $existingPaymentStatus = new PaymentStatus();
        $existingPaymentStatus->setPaymentStatus(PaymentStatuses::PAID_IN_FULL);
        $existingPaymentStatus->setForced(true);
        $updatedPaymentStatus = new PaymentStatus();
        $updatedPaymentStatus->setPaymentStatus($calculatedStatus);
        $targetEntityReference = new \stdClass();

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($existingPaymentStatus);

        $paymentStatusRepository = $this->createMock(PaymentStatusRepository::class);
        $paymentStatusRepository
            ->expects(self::once())
            ->method('upsertPaymentStatus')
            ->with($entityClass, $entityId, $calculatedStatus, true)
            ->willReturn($updatedPaymentStatus);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository, $paymentStatusRepository);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityReference')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntityReference);

        $this->paymentStatusCalculator
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($entity)
            ->willReturn($calculatedStatus);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentStatusUpdatedEvent::class));

        $result = $this->paymentStatusManager->updatePaymentStatus($entity, true);

        self::assertSame($updatedPaymentStatus, $result);
    }

    public function testUpdatePaymentStatusWhenNotExists(): void
    {
        $entity = new \stdClass();
        $entityClass = 'App\Entity\Order';
        $entityId = 123;
        $calculatedStatus = PaymentStatuses::PENDING;
        $createdPaymentStatus = new PaymentStatus();
        $createdPaymentStatus->setPaymentStatus($calculatedStatus);
        $targetEntityReference = new \stdClass();

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $paymentStatusRepository = $this->createMock(PaymentStatusRepository::class);
        $paymentStatusRepository
            ->expects(self::once())
            ->method('upsertPaymentStatus')
            ->with($entityClass, $entityId, $calculatedStatus, false)
            ->willReturn($createdPaymentStatus);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository, $paymentStatusRepository);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityReference')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntityReference);

        $this->paymentStatusCalculator
            ->expects(self::once())
            ->method('calculatePaymentStatus')
            ->with($entity)
            ->willReturn($calculatedStatus);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentStatusUpdatedEvent::class));

        $result = $this->paymentStatusManager->updatePaymentStatus($entity);

        self::assertSame($createdPaymentStatus, $result);
    }
}
