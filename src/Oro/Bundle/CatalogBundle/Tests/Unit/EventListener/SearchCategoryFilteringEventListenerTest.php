<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchCategoryFilteringEventListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsCategoryToQuery()
    {
        $categoryId = 23;

        /**
         * @var RequestProductHandler $requestProductHandler
         */
        $requestProductHandler = $this->getMock(RequestProductHandler::class);

        $requestProductHandler->expects($this->atLeastOnce())
            ->method('getCurrentCategoryId')
            ->will($this->returnValue($categoryId));

        $listener = new SearchCategoryFilteringEventListener(
            $requestProductHandler
        );

        /**
         * @var BuildAfter $event
         */
        $event = $this->getMock(BuildAfter::class);

        /**
         * @var SearchDatasource $searchDataSource
         */
        $datasource = $this->getMock(SearchDatasource::class);

        /**
         * @var SearchQuery $searchQuery
         */
        $query = $this->getMock(SearchQuery::class);

        $event->method('getDatasource')
            ->willReturn($datasource);

        $datasource->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('andWhere')
            ->with($categoryId);

        $listener->onBuildAfter($event);
    }

    /**
     * @var RequestProductHandler $requestProductHandler
     */
    private $requestProductHandler;

    /**
     * @param RequestProductHandler $requestProductHandler
     */
    public function __construct(RequestProductHandler $requestProductHandler)
    {
        $this->requestProductHandler = $requestProductHandler;
    }
}
