<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentStatusRepository;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusUpdatedEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentStatusManagerTest extends TestCase
{
    use EntityTrait;

    private PaymentStatusProviderInterface&MockObject $statusProvider;
    private DoctrineHelper&MockObject $doctrineHelper;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private PaymentStatusManager $manager;

    protected function setUp(): void
    {
        $this->statusProvider = $this->createMock(PaymentStatusProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->transaction = new PaymentTransaction();
        $this->transaction->setEntityClass(\stdClass::class);
        $this->transaction->setEntityIdentifier(1);
        $this->transaction->setPaymentMethod('payment_method');

        $this->manager = new PaymentStatusManager(
            $this->statusProvider,
            $this->doctrineHelper
        );
        $this->manager->setEventDispatcher($this->eventDispatcher);
    }

    public function testUpdateStatusNewEntity()
    {
        $entity = $this->getEntity(\stdClass::class);
        $repository = $this->commonExpectations($entity);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['entityClass' => \stdClass::class, 'entityIdentifier' => 1])
            ->willReturn(null);

        $this->statusProvider->expects($this->once())
            ->method('getPaymentStatus')
            ->with($entity)
            ->willReturn(PaymentStatuses::PAID_IN_FULL);

        $this->manager->updateStatus($this->transaction);
    }

    public function testOnTransactionCompleteExistingOrder()
    {
        $existingPaymentStatus = new PaymentStatus();
        $entity = $this->getEntity(\stdClass::class);
        $repository = $this->commonExpectations($entity);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['entityClass' => \stdClass::class, 'entityIdentifier' => 1])
            ->willReturn($existingPaymentStatus);

        $this->statusProvider->expects($this->once())
            ->method('getPaymentStatus')
            ->withConsecutive(
                [$entity],
                [$entity]
            )
            ->willReturnOnConsecutiveCalls(
                PaymentStatuses::PAID_PARTIALLY,
                PaymentStatuses::PAID_IN_FULL
            );

        $this->manager->updateStatus($this->transaction);
    }

    private function commonExpectations(object $entity): EntityRepository|\PHPUnit\Framework\MockObject\MockObject
    {
        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(\stdClass::class, 1)
            ->willReturn($entity);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository);

        $em = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(PaymentStatus::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PaymentStatus::class));
        $em->expects($this->once())
            ->method('flush')
            ->with($this->isInstanceOf(PaymentStatus::class));

        return $repository;
    }

    public function testGetPaymentStatusForEntityWhenNotExist(): void
    {
        $entity = new \stdClass();

        $entityRepository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(\stdClass::class, 1)
            ->willReturn($entity);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($entityRepository);

        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['entityClass' => \stdClass::class, 'entityIdentifier' => 1])
            ->willReturn(null);

        $paymentStatus = PaymentStatuses::PAID_IN_FULL;
        $this->statusProvider->expects($this->once())
            ->method('getPaymentStatus')
            ->with($entity)
            ->willReturn($paymentStatus);

        $paymentStatusEntity = $this->manager->getPaymentStatusForEntity(\stdClass::class, 1);
        $this->assertEquals($paymentStatus, $paymentStatusEntity->getPaymentStatus());
    }

    public function testGetPaymentStatusForEntityWhenExists(): void
    {
        $entityRepository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($entityRepository);

        $paymentStatus = PaymentStatuses::INVOICED;
        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['entityClass' => \stdClass::class, 'entityIdentifier' => 1])
            ->willReturn((new PaymentStatus())->setPaymentStatus($paymentStatus));

        $this->statusProvider->expects($this->never())
            ->method('getPaymentStatus');

        $paymentStatusEntity = $this->manager->getPaymentStatusForEntity(\stdClass::class, 1);
        $this->assertEquals($paymentStatus, $paymentStatusEntity->getPaymentStatus());
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

        $this->statusProvider
            ->expects(self::never())
            ->method('getPaymentStatus');

        $result = $this->manager->getPaymentStatus($entity);

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

        $this->statusProvider
            ->expects(self::once())
            ->method('getPaymentStatus')
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

        $result = $this->manager->getPaymentStatus($entity);

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

        $result = $this->manager->setPaymentStatus($entity, $paymentStatus, $force);

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

        $this->statusProvider
            ->expects(self::once())
            ->method('getPaymentStatus')
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

        $result = $this->manager->updatePaymentStatus($entity, false);

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

        $this->statusProvider
            ->expects(self::never())
            ->method('getPaymentStatus');

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $result = $this->manager->updatePaymentStatus($entity, false);

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

        $this->statusProvider
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($entity)
            ->willReturn($calculatedStatus);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentStatusUpdatedEvent::class));

        $result = $this->manager->updatePaymentStatus($entity, true);

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

        $this->statusProvider
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($entity)
            ->willReturn($calculatedStatus);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentStatusUpdatedEvent::class));

        $result = $this->manager->updatePaymentStatus($entity);

        self::assertSame($createdPaymentStatus, $result);
    }
}
