<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\SearchCategoryFilteringEventListener;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\SubcategoryProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Component\Testing\Unit\EntityTrait;

class SearchCategoryFilteringEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const CATEGORY_ID = 42;

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

        $this->category = $this->getEntity(Category::class, ['id' => self::CATEGORY_ID, 'materializedPath' => '1_42']);

        $this->repository->expects($this->any())
            ->method('find')
            ->with(self::CATEGORY_ID)
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

        $this->assertEquals([], $parameters->all());
    }

    /**
     * @dataProvider preBuildDataProvider
     *
     * @param array $parameters
     * @param int $categoryId
     * @param bool $includeSubcategories
     */
    public function testPreBuildWithCategoryId(array $parameters, $categoryId, $includeSubcategories)
    {
        $parameters = new ParameterBag($parameters);

        $this->requestProductHandler->expects($this->any())
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->requestProductHandler->expects($this->any())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn($includeSubcategories);

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

        $this->config->expects($this->at(0))
            ->method('offsetSetByPath')
            ->with(
                SearchCategoryFilteringEventListener::CATEGORY_ID_CONFIG_PATH,
                self::CATEGORY_ID
            );

        $this->config->expects($this->at(1))
            ->method('offsetSetByPath')
            ->with(
                SearchCategoryFilteringEventListener::INCLUDE_CAT_CONFIG_PATH,
                true
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
                                'choices' => [$subcategory1, $subcategory2]
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

        $this->assertEquals(
            ['categoryId' => self::CATEGORY_ID, 'includeSubcategories' => true],
            $parameters->all()
        );
    }

    /**
     * @return array
     */
    public function preBuildDataProvider()
    {
        return [
            'incorrect categoryId in parameters' => [
                'parameters' => [
                    'categoryId' => -100,
                    'includeSubcategories' => false,
                ],
                'categoryId' => self::CATEGORY_ID,
                'includeSubcategories' => true
            ],
            'categoryId in parameters' => [
                'parameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => true,
                ],
                'categoryId' => false,
                'includeSubcategories' => false
            ]
        ];
    }

    public function testPreBuildWithCategoryInRequest()
    {
        $categoryId = self::CATEGORY_ID;
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

        $event->method('getConfig')
            ->willReturn($this->config);

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            ['categoryId' => $categoryId, 'includeSubcategories' => $includeSubcategories],
            $parameters->all()
        );
    }

    public function testOnBuildAfterWithSingleCategory()
    {
        $categoryId = self::CATEGORY_ID;

        $event = $this->createMock(BuildAfter::class);

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

        $parameters = new ParameterBag(['categoryId' => $categoryId]);

        $dataGrid = new Datagrid('test', $this->config, $parameters);
        $dataGrid->setDatasource($datasource);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $datasource->method('getSearchQuery')
            ->willReturn($websiteSearchQuery);

        $this->listener->onBuildAfter($event);

        $this->assertEquals(['categoryId' => $categoryId], $parameters->all());
    }

    public function testOnBuildAfterWithIncludeSubcategories()
    {
        $categoryId = self::CATEGORY_ID;

        $this->config->expects($this->once())
            ->method('offsetAddToArrayByPath')
            ->with(
                SearchCategoryFilteringEventListener::VIEW_LINK_PARAMS_CONFIG_PATH,
                [
                    SluggableUrlGenerator::CONTEXT_TYPE => 'category',
                    SluggableUrlGenerator::CONTEXT_DATA => $categoryId
                ]
            );

        /** @var BuildAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(BuildAfter::class);

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDataSource */
        $datasource = $this->createMock(SearchDatasource::class);

        /** @var WebsiteSearchQuery|\PHPUnit_Framework_MockObject_MockObject $websiteSearchQuery */
        $websiteSearchQuery = $this->createMock(WebsiteSearchQuery::class);
        $websiteSearchQuery->expects($this->never())
            ->method($this->anything());

        $parameters = new ParameterBag(['categoryId' => $categoryId, 'includeSubcategories' => true]);

        $dataGrid = new Datagrid('test', $this->config, $parameters);
        $dataGrid->setDatasource($datasource);

        $event->method('getDatagrid')
            ->willReturn($dataGrid);

        $datasource->method('getSearchQuery')
            ->willReturn($websiteSearchQuery);

        $this->listener->onBuildAfter($event);
    }
}
