<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\EventListener\EntityTaxListener;

class EntityTaxListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var TaxManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $taxManager;

    /** @var EntityTaxListener */
    protected $listener;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject */
    protected $metadata;

    protected function setUp()
    {
        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->entityManager->expects($this->any())->method('getClassMetadata')->willReturn($this->metadata);

        $this->listener = new EntityTaxListener($this->taxManager);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->taxManager, $this->entityManager, $this->metadata);
    }

    public function testPrePersist()
    {
        $order = new Order();
        $taxValue = new TaxValue();
        $taxValue
            ->setEntityClass(ClassUtils::getRealClass($order));

        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->taxManager->expects($this->once())
            ->method('createTaxValue')
            ->with($order)
            ->willReturn($taxValue);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($taxValue);

        $this->metadata->expects($this->any())->method('getIdentifierValues')->willReturn([]);

        $this->listener->prePersist($order, $event);

        $this->assertEquals(0, $taxValue->getEntityId());
    }

    public function testPrePersistWithId()
    {
        $order = new Order();
        $taxValue = new TaxValue();
        $taxValue
            ->setEntityClass(ClassUtils::getRealClass($order));

        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->taxManager->expects($this->never())->method('createTaxValue');

        $this->metadata->expects($this->any())->method('getIdentifierValues')->willReturn([1]);

        $this->listener->prePersist($order, $event);

        $this->assertEquals(0, $taxValue->getEntityId());
    }

    public function testPreRemove()
    {
        $order = new Order();

        $this->taxManager
            ->expects($this->once())
            ->method('removeTax')
            ->with($order);

        $this->listener->preRemove($order);
    }

    public function testPostPersistWithoutTaxValue()
    {
        $order = new Order();
        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->entityManager->expects($this->never())
            ->method('getUnitOfWork');

        $this->listener->postPersist($order, $event);
    }

    public function testPostPersist()
    {
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())->method('getClassMetadata')->willReturn($metadata);

        // Prepare listener state
        $order = new Order();
        $taxValue = new TaxValue();
        $taxValue
            ->setEntityClass(ClassUtils::getRealClass($order));

        $event = new LifecycleEventArgs($order, $entityManager);

        $this->taxManager->expects($this->once())
            ->method('createTaxValue')
            ->with($order)
            ->willReturn($taxValue);

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($taxValue);

        $this->listener->prePersist($order, $event);

        $orderId = 1;
        $this->setValue($order, 'id', $orderId);

        // Test
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $uow->expects($this->once())
            ->method('propertyChanged')
            ->with($taxValue, 'entityId', null, $orderId);

        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($taxValue, ['entityId' => [null, $orderId]]);

        $uow->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($metadata, $taxValue);

        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $metadata->expects($this->any())->method('getIdentifierValues')->willReturn([$orderId]);

        $this->listener->postPersist($order, $event);

        $this->assertEquals($orderId, $taxValue->getEntityId());
    }

    public function testPreFlush()
    {
        $orderId = 1;
        /** @var Order $order */
        $order = $this->getEntity('\OroB2B\Bundle\OrderBundle\Entity\Order', ['id' => $orderId]);

        $event = new PreFlushEventArgs($this->entityManager);

        $this->taxManager->expects($this->once())
            ->method('saveTax')
            ->with($order, false);

        $this->metadata->expects($this->any())->method('getIdentifierValues')->willReturn([$orderId]);

        $this->listener->preFlush($order, $event);
    }
}
