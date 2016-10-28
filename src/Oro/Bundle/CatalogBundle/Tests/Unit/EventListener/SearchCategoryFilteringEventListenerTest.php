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

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $config */
    protected $config;

    protected function setUp()
    {
        $this->requestProductHandler = $this->getMockBuilder(RequestProductHandler::class)
            ->setMethods(['getCategoryId', 'getIncludeSubcategoriesChoice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();

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
            $this->doctrine
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
            $this->doctrine
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
            $this->doctrine
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

        $expr = Criteria::expr()->eq('integer.category_id', $categoryId);

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

        $mockedRepo = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()->getMock();

        $category = new Category();

        $mockedRepo->method('find')
            ->with($categoryId)->willReturn($category);

        $mockedRepo->method('getChildrenIds')
            ->with($category)->willReturn($subcategoryIds);

        $this->doctrine->method('getRepository')
            ->willReturn($mockedRepo);

        $listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $this->doctrine
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

        $categories   = $subcategoryIds;
        $categories[] = $categoryId;

        $expr = Criteria::expr()->in('integer.category_id', $categories);

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
