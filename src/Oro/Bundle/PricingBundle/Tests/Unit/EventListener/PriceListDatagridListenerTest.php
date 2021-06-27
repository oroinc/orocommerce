<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PricingBundle\EventListener\PriceListDatagridListener;

class PriceListDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PriceListDatagridListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->listener = new PriceListDatagridListener();
    }

    public function testOnBuildBefore()
    {
        $params = new ParameterBag();
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())->method('getParameters')->willReturn($params);
        /** @var BuildBefore|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getDatagrid')->willReturn($datagrid);
        $this->listener->onBuildBefore($event);
        $this->assertTrue($params->has('now'));
        $this->assertInstanceOf('\DateTime', $params->get('now'));
    }
}
