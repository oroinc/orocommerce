<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use OroB2B\Bundle\WarehouseBundle\EventListener\OrderWarehouseGridListener;

class OrderWarehouseGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $warehouseCounter;

    /**
     * @var OrderWarehouseGridListener
     */
    protected $orderWarehouseGridListener;

    protected function setUp()
    {
        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderWarehouseGridListener = new OrderWarehouseGridListener($this->warehouseCounter);
    }

    public function testOnBuildBeforeShouldDoNothing()
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

        $this->orderWarehouseGridListener->onBuildBefore($event);
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

        $config->expects($this->exactly(3))
            ->method('offsetSetByPath');

        $from = [['alias' => 'testAlias']];
        $config->expects($this->at(0))
            ->method('offsetGetByPath')
            ->with('[source][query][from]')
            ->willReturn($from);

        $select = [];
        $config->expects($this->at(1))
            ->method('offsetGetByPath')
            ->with('[source][query][select]')
            ->willReturn($select);

        $leftJoins = [];
        $config->expects($this->at(2))
            ->method('offsetGetByPath')
            ->willReturn($leftJoins);

        $this->orderWarehouseGridListener->onBuildBefore($event);
    }
}
