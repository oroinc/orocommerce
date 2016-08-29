<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class SearchCategoryFilteringEventListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsCategoryToQuery()
    {
        $categoryId = 23;

        /**
         * @var RequestProductHandler $requestProductHandler
         */
        $requestProductHandler = $this->getMockBuilder(RequestProductHandler::class)
            ->setMethods(['getCategoryId'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestProductHandler->expects($this->atLeastOnce())
            ->method('getCategoryId')
            ->will($this->returnValue($categoryId));

        $listener = new SearchCategoryFilteringEventListener(
            $requestProductHandler
        );

        /**
         * @var BuildAfter $event
         */
        $event = $this->getMockBuilder(BuildAfter::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * @var SearchDatasource $searchDataSource
         */
        $datasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * @var SearchQuery $searchQuery
         */
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * @var WebsiteSearchQuery $websiteSearchQuery
         */
        $websiteSearchQuery = $this->getMockBuilder(WebsiteSearchQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteSearchQuery->method('getQuery')
            ->will($this->returnValue($query));

        $dataGrid = $this->getMock(DatagridInterface::class);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $dataGrid->method('getDatasource')
            ->willReturn($datasource);

        $datasource->method('getQuery')
            ->willReturn($websiteSearchQuery);

        $query->expects($this->once())
            ->method('andWhere')
            ->with('integer.cat_id', '=', 23, 'integer');

        $listener->onBuildAfter($event);
    }
}
