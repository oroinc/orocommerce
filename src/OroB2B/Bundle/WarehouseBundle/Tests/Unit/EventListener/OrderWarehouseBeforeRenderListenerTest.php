<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\EventListener\OrderWarehouseBeforeRenderListener;

class OrderWarehouseBeforeRenderListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $warehouseCounter;

    /**
     * @var OrderWarehouseBeforeRenderListener
     */
    protected $orderWarehouseBeforeRenderListener;

    protected function setUp()
    {
        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderWarehouseBeforeRenderListener = new OrderWarehouseBeforeRenderListener($this->warehouseCounter);
    }

    public function testOnWarehouseOrderDisplayShouldDONothingIfNoWarehouse()
    {
        /** @var ValueRenderEvent|\PHPUnit_Framework_MockObject_MockObject $event * */
        $event = $this->getMockBuilder(ValueRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getFieldValue')
            ->willReturn('notWarehouse');
        $event->expects($this->never())
            ->method('getFieldConfigId');

        $this->orderWarehouseBeforeRenderListener->onWarehouseOrderDisplay($event);
    }

    public function testOnWarehouseOrderDisplayShouldDONothingIfNotOrder()
    {
        /** @var ValueRenderEvent|\PHPUnit_Framework_MockObject_MockObject $event * */
        $event = $this->getMockBuilder(ValueRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getFieldValue')
            ->willReturn($this->getMockBuilder(Warehouse::class)->getMock());
        $fieldConfigId = $this->getMockBuilder(FieldConfigId::class)->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getFieldConfigId')
            ->willReturn($fieldConfigId);
        $fieldConfigId->expects($this->once())
            ->method('getClassName')
            ->willReturn('notOrder');

        $this->warehouseCounter->expects($this->never())
            ->method('areMoreWarehouses');

        $this->orderWarehouseBeforeRenderListener->onWarehouseOrderDisplay($event);
    }

    public function testOnWarehouseOrderDisplayShouldHideField()
    {
        $fieldConfigId = $this->getMockBuilder(FieldConfigId::class)->disableOriginalConstructor()->getMock();
        $fieldConfigId->expects($this->once())
            ->method('getClassName')
            ->willReturn(Order::class);

        /** @var ValueRenderEvent $event * */
        $event = new ValueRenderEvent(new Warehouse(), new Warehouse(), $fieldConfigId);

        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(false);

        $this->assertTrue($event->isFieldVisible());
        $this->orderWarehouseBeforeRenderListener->onWarehouseOrderDisplay($event);
        $this->assertFalse($event->isFieldVisible());
    }
}
