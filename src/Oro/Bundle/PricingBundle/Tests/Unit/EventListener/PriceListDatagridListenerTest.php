<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PricingBundle\EventListener\PriceListDatagridListener;

class PriceListDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceListDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new PriceListDatagridListener();
    }

    public function testOnBuildBefore()
    {
        $params = new ParameterBag();
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);
        $event = $this->createMock(BuildBefore::class);
        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);
        $this->listener->onBuildBefore($event);
        $this->assertTrue($params->has('now'));
        $this->assertInstanceOf(\DateTime::class, $params->get('now'));
    }
}
