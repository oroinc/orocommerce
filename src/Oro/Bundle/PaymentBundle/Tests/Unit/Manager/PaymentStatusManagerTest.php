<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PaymentStatusManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var PaymentStatusProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $statusProviderMock;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelperMock;

    /** @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    /** @var PaymentStatusManager */
    protected $manager;

    /** @var PaymentTransaction */
    protected $transaction;

    protected function setUp()
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
        $this->transaction->setPaymentMethod('payment_term');

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

        $this->statusProviderMock->expects($this->once())->method('computeStatus')
            ->with($entity, new ArrayCollection([$this->transaction]))
            ->willReturn(PaymentStatusProvider::FULL);

        $this->manager->updateStatus($this->transaction);
    }

    public function testOnTransactionCompleteExistingOrder()
    {
        $existingPaymentStatus = new PaymentStatus();
        $existingTransaction = new PaymentTransaction();
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

        $transactionsCollection = new ArrayCollection([$this->transaction]);
        $transactionsCollection2 = new ArrayCollection([$existingTransaction, $this->transaction]);

        $this->statusProviderMock->expects($this->exactly(2))->method('computeStatus')
            ->withConsecutive(
                [$entity, $transactionsCollection],
                [$entity, $transactionsCollection2]
            )
            ->willReturnOnConsecutiveCalls(
                PaymentStatusProvider::PARTIALLY,
                PaymentStatusProvider::FULL
            );

        $this->paymentTransactionProvider->expects($this->once())->method('getPaymentTransactions')
            ->with($entity)
            ->willReturn([$existingTransaction]);

        $this->manager->updateStatus($this->transaction);
    }

    /**
     * @param object $entity
     * @return EntityRepository|\PHPUnit_Framework_MockObject_MockObject
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

        $this->doctrineHelperMock->expects($this->once())->method('getEntityManager')
            ->with(PaymentStatus::class)
            ->willReturn($emMock);

        $emMock->expects($this->once())->method('persist');

        return $repositoryMock;
    }
}
