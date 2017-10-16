<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Provider\SubcategoryProvider;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
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
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Component\Testing\Unit\EntityTrait;

class SearchCategoryFilteringEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestProductHandler;

    /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var SubcategoryProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryProvider;

    /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $config */
    protected $config;

    /** @var Category */
    protected $category;

    /** @var SearchCategoryFilteringEventListener */
    protected $listener;

    protected function setUp()
    {
        $this->requestProductHandler = $this->getMockBuilder(RequestProductHandler::class)
            ->setMethods(['getCategoryId', 'getIncludeSubcategoriesChoice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->createMock(CategoryRepository::class);

        $this->category = $this->getEntity(Category::class, ['id' => 42, 'materializedPath' => '1_42']);

        $this->repository->expects($this->any())
            ->method('find')
            ->willReturn($this->category);

        $this->categoryProvider = $this->createMock(SubcategoryProvider::class);
        $this->config = $this->createMock(DatagridConfiguration::class);

        $this->listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $this->repository,
            $this->categoryProvider
        );
    }

    public function testPreBuildWithoutCategory()
    {
        $parameters = new ParameterBag();

        /** @var PreBuild|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(PreBuild::class);
        $event->expects($this->any())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(null);

        $this->requestProductHandler->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(null);

        $this->config->expects($this->never())
            ->method($this->anything());

        $this->listener->onPreBuild($event);
    }

    public function testPreBuildWithCategoryInParameters()
    {
        $categoryId = 42;
        $includeSubcategories = true;
        $parameters = new ParameterBag(['categoryId' => $categoryId, 'includeSubcategories' => $includeSubcategories]);

        /** @var PreBuild|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(PreBuild::class);

        $event->expects($this->any())
            ->method('getParameters')
            ->willReturn($parameters);

        $subcategory1 = $this->getEntity(Category::class, ['id' => 1001, 'materializedPath' => '1_42_1001']);
        $subcategory2 = $this->getEntity(Category::class, ['id' => 2002, 'materializedPath' => '1_42_2002']);

        $this->categoryProvider->expects($this->once())
            ->method('getAvailableSubcategories')
            ->with($this->category)
            ->willReturn([$subcategory1, $subcategory2]);

        $this->config->expects($this->at(0))
            ->method('offsetSetByPath')
            ->with(SearchCategoryFilteringEventListener::CATEGORY_ID_CONFIG_PATH, $categoryId);

        $this->config->expects($this->at(1))
            ->method('offsetSetByPath')
            ->with(SearchCategoryFilteringEventListener::INCLUDE_CAT_CONFIG_PATH, $includeSubcategories);

        $this->config->expects($this->once())
            ->method('offsetGetByPath')
            ->with(Configuration::FILTERS_PATH)
            ->willReturn(
                [
                    'columns' => [
                        'some_filter' => ['options']
                    ],
                    'default' => [
                        'some_filter' => ['defaults']
                    ]
                ]
            );

        $this->config->expects($this->at(3))
            ->method('offsetSetByPath')
            ->with(
                Configuration::FILTERS_PATH,
                [
                    'columns' => [
                        'some_filter' => ['options'],
                        SubcategoryFilter::FILTER_TYPE_NAME => [
                            'data_name' => 'category_path',
                            'label' => 'oro.catalog.filter.subcategory.label',
                            'type' => SubcategoryFilter::FILTER_TYPE_NAME,
                            'rootCategory' => $this->category,
                            'options' => [
                                'categories' => [$subcategory1, $subcategory2]
                            ]
                        ]
                    ],
                    'default' => [
                        'some_filter' => ['defaults'],
                        SubcategoryFilter::FILTER_TYPE_NAME => [
                            'value' => []
                        ]
                    ]
                ]
            );

        $event->method('getConfig')
            ->willReturn($this->config);

        $this->listener->onPreBuild($event);
    }

    public function testPreBuildWithCategoryInRequest()
    {
        $categoryId = 42;
        $includeSubcategories = false;
        $parameters = new ParameterBag();

        /** @var PreBuild|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(PreBuild::class);

        $event->expects($this->any())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->requestProductHandler->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn($includeSubcategories);

        $this->config->expects($this->exactly(2))
            ->method('offsetSetByPath');

        $this->config->expects($this->at(0))
            ->method('offsetSetByPath')
            ->with(SearchCategoryFilteringEventListener::CATEGORY_ID_CONFIG_PATH, $categoryId);

        $this->config->expects($this->at(1))
            ->method('offsetSetByPath')
            ->with(SearchCategoryFilteringEventListener::INCLUDE_CAT_CONFIG_PATH, $includeSubcategories);

        $event->method('getConfig')
            ->willReturn($this->config);

        $this->listener->onPreBuild($event);
    }

    public function testOnBuildAfterWithSingleCategory()
    {
        $categoryId = 23;

        $event = $this->createMock(BuildAfter::class);

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

        $this->config->expects($this->once())
            ->method('offsetAddToArrayByPath')
            ->with(
                SearchCategoryFilteringEventListener::VIEW_LINK_PARAMS_CONFIG_PATH,
                [
                    SluggableUrlGenerator::CONTEXT_TYPE => 'category',
                    SluggableUrlGenerator::CONTEXT_DATA => $categoryId
                ]
            );

        $this->requestProductHandler
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(false);

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDataSource */
        $datasource = $this->createMock(SearchDatasource::class);

        /** @var SearchQuery|\PHPUnit_Framework_MockObject_MockObject $searchQuery */
        $query = $this->createMock(Query::class);

        /** @var WebsiteSearchQuery|\PHPUnit_Framework_MockObject_MockObject $websiteSearchQuery */
        $websiteSearchQuery = $this->createMock(WebsiteSearchQuery::class);

        $websiteSearchQuery->method('getQuery')
            ->will($this->returnValue($query));

        $expr = Criteria::expr()->eq('text.category_path', $this->category->getMaterializedPath());

        $websiteSearchQuery->expects($this->once())
            ->method('addWhere')
            ->with($expr);

        $dataGrid = $this->createMock(DatagridInterface::class);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $dataGrid->method('getDatasource')
            ->willReturn($datasource);

        $dataGrid->method('getConfig')
            ->willReturn($this->config);

        $datasource->method('getSearchQuery')
            ->willReturn($websiteSearchQuery);

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWithIncludeSubcategories()
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

        $this->config->expects($this->once())
            ->method('offsetAddToArrayByPath')
            ->with(
                SearchCategoryFilteringEventListener::VIEW_LINK_PARAMS_CONFIG_PATH,
                [
                    SluggableUrlGenerator::CONTEXT_TYPE => 'category',
                    SluggableUrlGenerator::CONTEXT_DATA => $categoryId
                ]
            );

        $this->repository->method('getChildrenIds')
            ->with($this->category)->willReturn($subcategoryIds);

        /** @var BuildAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(BuildAfter::class);

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDataSource */
        $datasource = $this->createMock(SearchDatasource::class);

        /** @var WebsiteSearchQuery|\PHPUnit_Framework_MockObject_MockObject $websiteSearchQuery */
        $websiteSearchQuery = $this->createMock(WebsiteSearchQuery::class);
        $websiteSearchQuery->expects($this->never())
            ->method($this->anything());

        $dataGrid = $this->createMock(DatagridInterface::class);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $dataGrid->method('getDatasource')
            ->willReturn($datasource);

        $dataGrid->method('getConfig')
            ->willReturn($this->config);

        $datasource->method('getSearchQuery')
            ->willReturn($websiteSearchQuery);

        $this->listener->onBuildAfter($event);
    }
}
