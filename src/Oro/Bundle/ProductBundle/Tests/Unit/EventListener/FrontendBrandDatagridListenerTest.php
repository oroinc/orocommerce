<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ProductBundle\EventListener\FrontendBrandDatagridListener;
use Oro\Bundle\ProductBundle\Filter\FrontendBrandFilter;
use PHPUnit\Framework\TestCase;

class FrontendBrandDatagridListenerTest extends TestCase
{
    public function testOnBuildBefore(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects(self::once())
            ->method('offsetExistByPath')
            ->with('[filters][columns][brand][type]')
            ->willReturn(true);
        $config->expects(self::once())
            ->method('offsetSetByPath')
            ->with('[filters][columns][brand][type]', FrontendBrandFilter::FILTER_ALIAS);

        $listener = new FrontendBrandDatagridListener();
        $listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $config));
    }

    public function testOnBuildBeforeNoBrandFilter(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects(self::once())
            ->method('offsetExistByPath')
            ->with('[filters][columns][brand][type]')
            ->willReturn(false);
        $config->expects(self::never())
            ->method('offsetSetByPath');

        $listener = new FrontendBrandDatagridListener();
        $listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $config));
    }
}
