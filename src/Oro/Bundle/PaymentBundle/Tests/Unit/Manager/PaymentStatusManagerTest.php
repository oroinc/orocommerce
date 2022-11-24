<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class PaymentStatusManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PaymentStatusProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $statusProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTransactionProvider;

    /** @var PaymentStatusManager */
    private $manager;

    /** @var PaymentTransaction */
    private $transaction;

    protected function setUp(): void
    {
        $this->statusProvider = $this->createMock(PaymentStatusProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);

        $this->transaction = new PaymentTransaction();
        $this->transaction->setEntityClass(\stdClass::class);
        $this->transaction->setEntityIdentifier(1);
        $this->transaction->setPaymentMethod('payment_method');

        $this->manager = new PaymentStatusManager(
            $this->statusProvider,
            $this->doctrineHelper,
            $this->paymentTransactionProvider
        );
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
            ->willReturn(PaymentStatusProvider::FULL);

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
                PaymentStatusProvider::PARTIALLY,
                PaymentStatusProvider::FULL
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

        $paymentStatus = PaymentStatusProvider::FULL;
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

        $paymentStatus = PaymentStatusProvider::INVOICED;
        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['entityClass' => \stdClass::class, 'entityIdentifier' => 1])
            ->willReturn((new PaymentStatus())->setPaymentStatus($paymentStatus));

        $this->statusProvider->expects($this->never())
            ->method('getPaymentStatus');

        $paymentStatusEntity = $this->manager->getPaymentStatusForEntity(\stdClass::class, 1);
        $this->assertEquals($paymentStatus, $paymentStatusEntity->getPaymentStatus());
    }
}
