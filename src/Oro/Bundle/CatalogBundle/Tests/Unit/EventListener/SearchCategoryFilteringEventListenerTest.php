<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\SearchCategoryFilteringEventListener;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class SearchCategoryFilteringEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestProductHandler;

    /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $config */
    protected $config;

    /** @var Category */
    protected $category;

    protected function setUp()
    {
        $this->requestProductHandler = $this->getMockBuilder(RequestProductHandler::class)
            ->setMethods(['getCategoryId', 'getIncludeSubcategoriesChoice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()->getMock();

        $this->category = new Category();
        $this->category->setMaterializedPath('1_23');

        $this->repository->expects($this->any())
            ->method('find')
            ->willReturn($this->category);

        $this->config = $this->getMockBuilder(DatagridConfiguration::class)
            ->disableOriginalConstructor()->getMock();
    }

    public function testPreBuildWithoutCategory()
    {
        /** @var PreBuild|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(PreBuild::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getParameters'])
            ->getMock();

        $event->expects($this->any())
            ->method('getParameters')
            ->willReturnSelf();

        $event->expects($this->any())
            ->method('get')
            ->withAnyParameters()
            ->willReturn(null);

        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(null);

        $this->requestProductHandler->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(null);

        $listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $this->repository
        );

        $listener->onPreBuild($event);
    }

    public function testPreBuildWithCategory()
    {
        $categoryId = 1;

        /** @var PreBuild|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(PreBuild::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getParameters', 'getConfig'])
            ->getMock();

        $event->expects($this->any())
            ->method('getParameters')
            ->willReturnSelf();

        $event->expects($this->any())
            ->method('get')
            ->withAnyParameters()
            ->willReturn($categoryId); // categoryId, includeSubcategories

        $listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $this->repository
        );

        $this->config->expects($this->at(0))
            ->method('offsetSetByPath')
            ->with(SearchCategoryFilteringEventListener::CATEGORY_ID_CONFIG_PATH, $categoryId);

        $this->config->expects($this->at(1))
            ->method('offsetSetByPath')
            ->with(SearchCategoryFilteringEventListener::INCLUDE_CAT_CONFIG_PATH, $categoryId);

        $event->method('getConfig')
            ->willReturn($this->config);

        $listener->onPreBuild($event);
    }

    public function testOnBuildAfterWithSingleCategory()
    {
        $categoryId = 23;

        $event = $this->getMockBuilder(BuildAfter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config
            ->expects($this->at(0))
            ->method('offsetGetByPath')
            ->with(SearchCategoryFilteringEventListener::CATEGORY_ID_CONFIG_PATH)
            ->willReturn($categoryId);

        $this->config
            ->expects($this->at(1))
            ->method('offsetGetByPath')
            ->with(SearchCategoryFilteringEventListener::INCLUDE_CAT_CONFIG_PATH)
            ->willReturn(null);

        $this->requestProductHandler
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(false);

        $listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $this->repository
        );

        /** @var BuildAfter|\PHPUnit_Framework_MockObject_MockObject $event */

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDataSource */
        $datasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SearchQuery|\PHPUnit_Framework_MockObject_MockObject $searchQuery */
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var WebsiteSearchQuery|\PHPUnit_Framework_MockObject_MockObject $websiteSearchQuery */
        $websiteSearchQuery = $this->getMockBuilder(WebsiteSearchQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteSearchQuery->method('getQuery')
            ->will($this->returnValue($query));

        $expr = Criteria::expr()->startsWith('text.cat_path', $this->category->getMaterializedPath());

        $websiteSearchQuery->expects($this->once())
            ->method('addWhere')
            ->with($expr);

        $dataGrid = $this->getMock(DatagridInterface::class);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $dataGrid->method('getDatasource')
            ->willReturn($datasource);

        $dataGrid->method('getConfig')
            ->willReturn($this->config);

        $datasource->method('getSearchQuery')
            ->willReturn($websiteSearchQuery);

        $listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithMultipleCategories()
    {
        $categoryId     = 11;
        $subcategoryIds = [1, 2, 6, 10];

        $this->config
            ->expects($this->at(0))
            ->method('offsetGetByPath')
            ->with(SearchCategoryFilteringEventListener::CATEGORY_ID_CONFIG_PATH)
            ->willReturn($categoryId);

        $this->config
            ->expects($this->at(1))
            ->method('offsetGetByPath')
            ->with(SearchCategoryFilteringEventListener::INCLUDE_CAT_CONFIG_PATH)
            ->willReturn($subcategoryIds);

        $this->repository->method('getChildrenIds')
            ->with($this->category)->willReturn($subcategoryIds);

        $listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $this->repository
        );

        /** @var BuildAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(BuildAfter::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDataSource */
        $datasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var SearchQuery|\PHPUnit_Framework_MockObject_MockObject $searchQuery */
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var WebsiteSearchQuery|\PHPUnit_Framework_MockObject_MockObject $websiteSearchQuery */
        $websiteSearchQuery = $this->getMockBuilder(WebsiteSearchQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expr = Criteria::expr()->startsWith('text.cat_path', $this->category->getMaterializedPath());

        $websiteSearchQuery->expects($this->once())
            ->method('addWhere')
            ->with($expr);

        $dataGrid = $this->getMock(DatagridInterface::class);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $dataGrid->method('getDatasource')
            ->willReturn($datasource);

        $dataGrid->method('getConfig')
            ->willReturn($this->config);

        $websiteSearchQuery->method('getQuery')
            ->will($this->returnValue($query));

        $datasource->method('getSearchQuery')
            ->willReturn($websiteSearchQuery);

        $listener->onBuildAfter($event);
    }
}
