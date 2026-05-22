<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\OrderLineItemsDraftFilterExtension;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemsDraftFilterExtensionTest extends TestCase
{
    private DraftSessionOrmFilterManager&MockObject $filterManager;
    private OrderLineItemsDraftFilterExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->filterManager = $this->createMock(DraftSessionOrmFilterManager::class);
        $this->extension = new OrderLineItemsDraftFilterExtension($this->filterManager);
    }

    public function testIsApplicableForOrderLineItemsEditGrid(): void
    {
        $config = DatagridConfiguration::create(['name' => 'order-line-items-edit-grid']);

        self::assertTrue($this->extension->isApplicable($config));
    }

    public function testIsNotApplicableForOtherGrids(): void
    {
        $config = DatagridConfiguration::create(['name' => 'some-other-grid']);

        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testSetDatagridName(): void
    {
        $this->extension->setDatagridName('custom-grid-name');

        $config = DatagridConfiguration::create(['name' => 'custom-grid-name']);
        self::assertTrue($this->extension->isApplicable($config));

        $oldConfig = DatagridConfiguration::create(['name' => 'order-line-items-edit-grid']);
        self::assertFalse($this->extension->isApplicable($oldConfig));
    }

    public function testVisitDatasourceDisablesFilter(): void
    {
        $config = DatagridConfiguration::create(['name' => 'order-line-items-edit-grid']);
        $datasource = $this->createMock(OrmDatasource::class);

        $this->filterManager->expects(self::once())
            ->method('disable');

        $this->extension->visitDatasource($config, $datasource);
    }

    public function testVisitDatasourceDoesNothingForNonOrmDatasource(): void
    {
        $config = DatagridConfiguration::create(['name' => 'order-line-items-edit-grid']);
        $datasource = $this->createMock(DatasourceInterface::class);

        $this->filterManager->expects(self::never())
            ->method('disable');

        $this->extension->visitDatasource($config, $datasource);
    }

    public function testVisitResultEnablesFilter(): void
    {
        $config = DatagridConfiguration::create(['name' => 'order-line-items-edit-grid']);
        $result = $this->createMock(ResultsObject::class);

        $this->filterManager->expects(self::once())
            ->method('enable');

        $this->extension->visitResult($config, $result);
    }

    public function testGetPriorityReturnsHighValue(): void
    {
        self::assertSame(300, $this->extension->getPriority());
    }
}
