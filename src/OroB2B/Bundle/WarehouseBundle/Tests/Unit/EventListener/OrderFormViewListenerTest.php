<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use OroB2B\Bundle\WarehouseBundle\EventListener\OrderFormViewListener;

class OrderFormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $warehouseCounter;

    /**
     * @var OrderFormViewListener
     */
    protected $orderFormViewListener;

    protected function setUp()
    {
        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFormViewListener = new OrderFormViewListener($this->warehouseCounter);
    }

    public function testOnOrderEditShouldDoNothing()
    {
        /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject $event * */
        $event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(false);

        $this->orderFormViewListener->onOrderEdit($event);
    }

    public function testOnOrderEdit()
    {
        /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject $event * */
        $event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(true);


        $env = $this->getMockBuilder(\Twig_Environment::class)->getMock();
        $env->expects($this->once())
            ->method('render');
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $scrollData = $this->getMockBuilder(ScrollData::class)->getMock();
        $scrollData->expects($this->once())
            ->method('addSubBlockData');
        $event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);

        $this->orderFormViewListener->onOrderEdit($event);
    }
}
