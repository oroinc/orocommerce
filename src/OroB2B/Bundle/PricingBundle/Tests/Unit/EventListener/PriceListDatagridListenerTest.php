<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\PricingBundle\EventListener\PriceListDatagridListener;

class PriceListDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListDatagridListener
     */
    protected $listener;

    public function setUp()
    {
        $this->listener = new PriceListDatagridListener();
    }

    public function testOnBuildBefore()
    {
        $params = new ParameterBag();
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())->method('getParameters')->willReturn($params);
        /** @var BuildBefore|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getDatagrid')->willReturn($datagrid);
        $this->listener->onBuildBefore($event);
        $this->assertTrue($params->has('now'));
        $this->assertInstanceOf('\DateTime', $params->get('now'));
    }
}
