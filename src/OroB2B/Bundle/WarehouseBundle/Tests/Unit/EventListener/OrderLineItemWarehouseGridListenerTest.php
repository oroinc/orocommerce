<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use OroB2B\Bundle\WarehouseBundle\EventListener\OrderLineItemWarehouseGridListener;

class OrderLineItemWarehouseGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $warehouseCounter;

    /**
     * @var OrderLineItemWarehouseGridListener
     */
    protected $orderLineItemWarehouseGridListener;

    protected function setUp()
    {
        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderLineItemWarehouseGridListener = new OrderLineItemWarehouseGridListener($this->warehouseCounter);
    }

    public function testOnBuildBefore()
    {
        /** @var BuildBefore|\PHPUnit_Framework_MockObject_MockObject $event * */
        $event = $this->getMockBuilder(BuildBefore::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(true);

        $config = $this->getMockBuilder(DatagridConfiguration::class)->disableOriginalConstructor()->getMock();

        $event->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $config->expects($this->once())
            ->method('offsetSetByPath');

        $this->orderLineItemWarehouseGridListener->onBuildBefore($event);
    }

    public function testOnBuildBeforeDoesNothing()
    {
        /** @var BuildBefore|\PHPUnit_Framework_MockObject_MockObject $event * */
        $event = $this->getMockBuilder(BuildBefore::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(false);

        $event->expects($this->never())
            ->method('getConfig');

        $this->orderLineItemWarehouseGridListener->onBuildBefore($event);
    }
}
