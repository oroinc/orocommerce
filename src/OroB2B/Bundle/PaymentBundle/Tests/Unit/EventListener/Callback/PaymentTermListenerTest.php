<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\EventListener\Callback\PaymentTermListener;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $propertyAccessor;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /** @var PaymentTermListener */
    protected $listener;

    protected function setUp()
    {
        $this->propertyAccessor = $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransaction = new PaymentTransaction();

        $this->listener = new PaymentTermListener(
            $this->propertyAccessor,
            $this->doctrineHelper,
            $this->paymentTermProvider
        );
    }

    protected function tearDown()
    {
        unset(
            $this->listener,
            $this->paymentTransaction,
            $this->paymentTermProvider,
            $this->doctrineHelper,
            $this->propertyAccessor
        );
    }

    public function testOnReturnTransactionNotSuccessful()
    {
        $this->paymentTransaction
            ->setSuccessful(false);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntity');

        $this->listener->onReturn($event);
    }

    public function testOnReturnNoEntity()
    {
        $entityClass = 'TestClass';
        $entityId = 10;
        $this->paymentTransaction
            ->setEntityClass($entityClass)
            ->setEntityIdentifier($entityId)
            ->setSuccessful(true);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with($entityClass, $entityId)
            ->willReturn(null);

        $this->listener->onReturn($event);

        $this->paymentTermProvider->expects($this->never())
            ->method('getCurrentPaymentTerm');
    }

    public function testOnReturnNoPaymentTerm()
    {
        $entity = new \stdClass();
        $entityClass = 'TestClass';
        $entityId = 10;
        $this->paymentTransaction
            ->setEntityClass($entityClass)
            ->setEntityIdentifier($entityId)
            ->setSuccessful(true);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with($entityClass, $entityId)
            ->willReturn($entity);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn(null);

        $this->listener->onReturn($event);

        $this->propertyAccessor->expects($this->never())
            ->method('setValue');
    }

    public function testOnReturn()
    {
        $entityClass = 'TestClass';
        $entityId = 10;
        $entity = new \stdClass();
        $paymentTerm = new \stdClass();

        $this->paymentTransaction
            ->setEntityClass($entityClass)
            ->setEntityIdentifier($entityId)
            ->setSuccessful(true);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with($entityClass, $entityId)
            ->willReturn($entity);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn($paymentTerm);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'paymentTerm', $paymentTerm);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->once())
            ->method('flush')
            ->with($entity);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($entityManager);

        $this->listener->onReturn($event);
    }
}
