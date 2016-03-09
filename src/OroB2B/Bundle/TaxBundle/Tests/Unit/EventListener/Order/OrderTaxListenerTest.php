<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener\Order;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\EventListener\Order\OrderTaxListener;

class OrderTaxListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var TaxManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $taxManager;

    /** @var OrderTaxListener */
    protected $listener;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    protected function setUp()
    {
        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new OrderTaxListener($this->taxManager);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->taxManager);
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

        $this->listener->prePersist($order, $event);

        $this->assertEquals(0, $taxValue->getEntityId());
    }

    public function testPreRemove()
    {
        $order = new Order();
        $event = new LifecycleEventArgs($order, $this->entityManager);

        $this->taxManager
            ->expects($this->once())
            ->method('removeTax')
            ->with($order);

        $this->listener->preRemove($order, $event);
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
        // Prepare listener state
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
            ->with($taxValue, [
                'entityId' => [null, $orderId]
            ]);

        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->listener->postPersist($order, $event);

        $this->assertEquals($orderId, $taxValue->getEntityId());
    }

    public function testPreFlush()
    {
        /** @var Order $order */
        $order = $this->getEntity('\OroB2B\Bundle\OrderBundle\Entity\Order', ['id' => 1]);

        $newLineItem = new OrderLineItem();
        $order->addLineItem($newLineItem);

        /** @var OrderLineItem $existsLineItem */
        $existsLineItem = $this->getEntity('\OroB2B\Bundle\OrderBundle\Entity\OrderLineItem', ['id' => 100]);
        $order->addLineItem($existsLineItem);

        $event = new PreFlushEventArgs($this->entityManager);

        $this->taxManager->expects($this->exactly(2))
            ->method('saveTax')
            ->withConsecutive(
                [$order, false],
                [$existsLineItem]
            );

        $newLineItemTaxValue = new TaxValue();
        $newLineItemTaxValue
            ->setEntityClass(ClassUtils::getRealClass($newLineItem));

        $this->taxManager->expects($this->once())
            ->method('createTaxValue')
            ->with($newLineItem)
            ->willReturn($newLineItemTaxValue);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($newLineItemTaxValue);

        $this->listener->preFlush($order, $event);
    }
}
