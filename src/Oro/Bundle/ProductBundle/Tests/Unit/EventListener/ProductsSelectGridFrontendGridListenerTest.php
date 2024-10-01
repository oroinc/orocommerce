<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\EventListener\ProductsSelectGridFrontendGridListener;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use PHPUnit\Framework\TestCase;

class ProductsSelectGridFrontendGridListenerTest extends TestCase
{
    private ProductsSelectGridFrontendGridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new ProductsSelectGridFrontendGridListener();
    }

    public function testOnBuildAfterNotSearchDatasource(): void
    {
        $datasource = $this->createMock(OrmDatasource::class);

        $datagrid = $this->createMock(Datagrid::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $event = new BuildAfter($datagrid);

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterAllTypesAreAllowed(): void
    {
        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects(self::never())
            ->method('getSearchQuery');

        $datagrid = $this->createMock(Datagrid::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $event = new BuildAfter($datagrid);

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfter(): void
    {
        $notAllowedProductTypes = ['foo', 'bar'];

        $criteria = $this->createMock(Criteria::class);
        $criteria->expects(self::once())
            ->method('andWhere')
            ->with(
                Criteria::expr()->notIn(
                    'text.type',
                    $notAllowedProductTypes
                )
            );

        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $searchQuery->expects(self::once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects(self::once())
            ->method('getSearchQuery')
            ->willReturn($searchQuery);

        $datagrid = $this->createMock(Datagrid::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $event = new BuildAfter($datagrid);

        $this->listener->setNotAllowedProductTypes($notAllowedProductTypes);
        $this->listener->onBuildAfter($event);
    }
}
