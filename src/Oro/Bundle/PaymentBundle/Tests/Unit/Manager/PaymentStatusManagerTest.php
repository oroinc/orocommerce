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
    protected $statusProviderMock;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelperMock;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentTransactionProvider;

    /** @var PaymentStatusManager */
    protected $manager;

    /** @var PaymentTransaction */
    protected $transaction;

    protected function setUp(): void
    {
        $this->statusProviderMock = $this->getMockBuilder(PaymentStatusProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->doctrineHelperMock = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->paymentTransactionProvider = $this->getMockBuilder(PaymentTransactionProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->transaction = new PaymentTransaction();
        $this->transaction->setEntityClass('\stdClass');
        $this->transaction->setEntityIdentifier(1);
        $this->transaction->setPaymentMethod('payment_method');

        $this->manager = new PaymentStatusManager(
            $this->statusProviderMock,
            $this->doctrineHelperMock,
            $this->paymentTransactionProvider
        );
    }

    public function testUpdateStatusNewEntity()
    {
        $entity = $this->getEntity('\stdClass');
        $repositoryMock = $this->commonExpectations($entity);

        $repositoryMock->expects($this->once())->method('findOneBy')
            ->with(
                [
                    'entityClass' => '\stdClass',
                    'entityIdentifier' => 1,
                ]
            )
            ->willReturn(null);

        $this->statusProviderMock->expects($this->once())->method('getPaymentStatus')
            ->with($entity)
            ->willReturn(PaymentStatusProvider::FULL);

        $this->manager->updateStatus($this->transaction);
    }

    public function testOnTransactionCompleteExistingOrder()
    {
        $existingPaymentStatus = new PaymentStatus();
        $entity = $this->getEntity('\stdClass');
        $repositoryMock = $this->commonExpectations($entity);

        $repositoryMock->expects($this->once())->method('findOneBy')
            ->with(
                [
                    'entityClass' => '\stdClass',
                    'entityIdentifier' => 1,
                ]
            )
            ->willReturn($existingPaymentStatus);

        $this->statusProviderMock->expects($this->once())->method('getPaymentStatus')
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

    /**
     * @param object $entity
     * @return EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function commonExpectations($entity)
    {
        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()->getMock();

        $this->doctrineHelperMock->expects($this->once())->method('getEntityReference')
            ->with('\stdClass', 1)
            ->willReturn($entity);

        $this->doctrineHelperMock->expects($this->once())->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repositoryMock);

        $emMock = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->doctrineHelperMock->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(PaymentStatus::class)
            ->willReturn($emMock);

        $emMock->expects($this->once())->method('persist')->with($this->isInstanceOf(PaymentStatus::class));
        $emMock->expects($this->once())->method('flush')->with($this->isInstanceOf(PaymentStatus::class));

        return $repositoryMock;
    }

    public function testGetPaymentStatusForEntityWhenNotExist(): void
    {
        $entity = new \stdClass();

        $entityRepository = $this->createMock(EntityRepository::class);

        $this->doctrineHelperMock
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(\stdClass::class, 1)
            ->willReturn($entity);

        $this->doctrineHelperMock
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($entityRepository);

        $entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['entityClass' => \stdClass::class, 'entityIdentifier' => 1])
            ->willReturn(null);

        $paymentStatus = PaymentStatusProvider::FULL;
        $this->statusProviderMock
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($entity)
            ->willReturn($paymentStatus);

        $paymentStatusEntity = $this->manager->getPaymentStatusForEntity(\stdClass::class, 1);
        $this->assertEquals($paymentStatus, $paymentStatusEntity->getPaymentStatus());
    }

    public function testGetPaymentStatusForEntityWhenExists(): void
    {
        $entityRepository = $this->createMock(EntityRepository::class);

        $this->doctrineHelperMock
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(PaymentStatus::class)
            ->willReturn($entityRepository);

        $paymentStatus = PaymentStatusProvider::INVOICED;
        $entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['entityClass' => \stdClass::class, 'entityIdentifier' => 1])
            ->willReturn((new PaymentStatus())->setPaymentStatus($paymentStatus));

        $this->statusProviderMock
            ->expects($this->never())
            ->method('getPaymentStatus');

        $paymentStatusEntity = $this->manager->getPaymentStatusForEntity(\stdClass::class, 1);
        $this->assertEquals($paymentStatus, $paymentStatusEntity->getPaymentStatus());
    }
}
