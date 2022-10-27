<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\SearchCategoryFilteringEventListener;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\SubcategoryProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType\Stub\ContentVariantStub;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class SearchCategoryFilteringEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const CONTENT_VARIANT_ID = 142;
    private const CONTENT_VARIANT_OTHER_TYPE_ID = 242;
    private const CATEGORY_ID = 42;

    /** @var RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $requestProductHandler;

    /** @var SubcategoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryProvider;

    /** @var DatagridConfiguration */
    private $config;

    /** @var Category|null */
    private $category;

    /** @var SearchCategoryFilteringEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->requestProductHandler = $this->createMock(RequestProductHandler::class);

        $this->category = $this->createCategory(self::CATEGORY_ID, '1_42');

        $categoryProvider = $this->createMock(SubcategoryProvider::class);
        $categoryProvider->expects($this->any())
            ->method('getAvailableSubcategories')
            ->willReturnMap([
                [$this->category, [$this->createCategory(1001, '1_42_1001'), $this->createCategory(2002, '1_42_2002')]]
            ]);

        $this->config = DatagridConfiguration::create(
            [
                'filters' => [
                    'columns' => [
                        'some_filter' => ['options'],
                    ],
                    'default' => [
                        'some_filter' => ['defaults'],
                    ],
                ],
            ]
        );

        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnMap([[self::CATEGORY_ID, null, null, $this->category]]);

        $categoryEntityManager = $this->createMock(ObjectManager::class);
        $categoryEntityManager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($repository);

        $contentVariantEntityManager = $this->createMock(EntityManagerInterface::class);
        $contentVariant = (new ContentVariantStub())
            ->setId(self::CONTENT_VARIANT_ID)
            ->setType(CategoryPageContentVariantType::TYPE);
        $contentVariantOfOtherType = (new ContentVariantStub())
            ->setId(self::CONTENT_VARIANT_OTHER_TYPE_ID)
            ->setType('sample_type');
        $contentVariantEntityManager
            ->expects($this->any())
            ->method('find')
            ->willReturnMap([
                [ContentVariant::class, self::CONTENT_VARIANT_ID, $contentVariant],
                [ContentVariant::class, self::CONTENT_VARIANT_OTHER_TYPE_ID, $contentVariantOfOtherType],
            ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Category::class, $categoryEntityManager],
                [ContentVariant::class, $contentVariantEntityManager],
            ]);

        $this->listener = new SearchCategoryFilteringEventListener(
            $this->requestProductHandler,
            $doctrine,
            $categoryProvider
        );
    }

    public function testPreBuildWithoutCategory()
    {
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(0);
        $this->requestProductHandler->expects($this->never())
            ->method('getIncludeSubcategoriesChoice');
        $this->requestProductHandler->expects($this->never())
            ->method('getCategoryContentVariantId');
        $this->requestProductHandler->expects($this->never())
            ->method('getOverrideVariantConfiguration');

        $event = new PreBuild($this->config, new ParameterBag());
        $this->listener->onPreBuild($event);

        $this->assertEquals([], $event->getParameters()->all());
        $this->assertEquals([], $event->getConfig()->toArray(['options']));
    }

    /**
     * @dataProvider preBuildWithCategoryIdDataProvider
     */
    public function testPreBuildWithCategoryId(
        array $parameters,
        array $expectedParameters,
        array $expectedConfig
    ): void {
        $this->requestProductHandler->expects($this->any())
            ->method('getCategoryContentVariantId')
            ->willReturn(0);
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(0);
        $this->requestProductHandler->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->with($parameters['includeSubcategories'])
            ->willReturn($parameters['includeSubcategories']);

        $event = new PreBuild($this->config, new ParameterBag($parameters));
        $this->listener->onPreBuild($event);

        $this->assertEquals($expectedParameters, $event->getParameters()->all());
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    public function preBuildWithCategoryIdDataProvider(): array
    {
        return [
            'category does not exist' => [
                'parameters' => [
                    'categoryId' => 101,
                    'includeSubcategories' => false,
                ],
                'expectedParameters' => [
                    'categoryId' => 101,
                    'includeSubcategories' => false,
                ],
                'expectedConfig' => [
                    'filters' => [
                        'columns' => [
                            'some_filter' => [
                                0 => 'options',
                            ],
                        ],
                        'default' => [
                            'some_filter' => [
                                0 => 'defaults',
                            ],
                        ],
                    ],
                    'options' => [
                        'urlParams' => [
                            'categoryId' => 101,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
            'category exists' => [
                'parameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => true,
                ],
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => true,
                ],
                'expectedConfig' => [
                    'filters' => [
                        'columns' => [
                            'some_filter' => [
                                0 => 'options',
                            ],
                            'subcategory' => [
                                'data_name' => 'category_paths',
                                'label' => 'oro.catalog.filter.subcategory.label',
                                'type' => 'subcategory',
                                'rootCategory' => $this->createCategory(self::CATEGORY_ID, '1_42'),
                                'options' => [
                                    'choices' => [
                                        $this->createCategory(1001, '1_42_1001'),
                                        $this->createCategory(2002, '1_42_2002'),
                                    ],
                                ],
                            ],
                        ],
                        'default' => [
                            'some_filter' => [
                                0 => 'defaults',
                            ],
                            'subcategory' => [
                                'value' => [
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'urlParams' => [
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider preBuildWithCategoryInRequestDataProvider
     */
    public function testPreBuildWithCategoryInRequest(
        array $parameters,
        int $categoryId,
        bool $includeSubcategories,
        array $expectedParameters,
        array $expectedConfig
    ): void {
        $this->requestProductHandler->expects($this->any())
            ->method('getCategoryContentVariantId')
            ->willReturn(0);
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($categoryId);
        $this->requestProductHandler->expects($this->any())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn($includeSubcategories);

        $event = new PreBuild($this->config, new ParameterBag($parameters));
        $this->listener->onPreBuild($event);

        $this->assertEquals($expectedParameters, $event->getParameters()->all());
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function preBuildWithCategoryInRequestDataProvider(): array
    {
        $filters = [
            'columns' => [
                'some_filter' => [
                    0 => 'options',
                ],
                'subcategory' => [
                    'data_name' => 'category_paths',
                    'label' => 'oro.catalog.filter.subcategory.label',
                    'type' => 'subcategory',
                    'rootCategory' => $this->createCategory(self::CATEGORY_ID, '1_42'),
                    'options' => [
                        'choices' => [
                            $this->createCategory(1001, '1_42_1001'),
                            $this->createCategory(2002, '1_42_2002'),
                        ],
                    ],
                ],
            ],
            'default' => [
                'some_filter' => [
                    0 => 'defaults',
                ],
                'subcategory' => [
                    'value' => [],
                ],
            ],
        ];

        return [
            'category id is not specified' => [
                'parameters' => [],
                'categoryId' => 0,
                'includeSubcategories' => false,
                'expectedParameters' => [],
                'expectedConfig' => [
                    'filters' => [
                        'columns' => [
                            'some_filter' => [
                                0 => 'options',
                            ],
                        ],
                        'default' => [
                            'some_filter' => [
                                0 => 'defaults',
                            ],
                        ],
                    ],
                ],
            ],
            'category does not exist' => [
                'parameters' => [],
                'categoryId' => 101,
                'includeSubcategories' => false,
                'expectedParameters' => [
                    'categoryId' => 101,
                    'includeSubcategories' => false,
                ],
                'expectedConfig' => [
                    'filters' => [
                        'columns' => [
                            'some_filter' => [
                                0 => 'options',
                            ],
                        ],
                        'default' => [
                            'some_filter' => [
                                0 => 'defaults',
                            ],
                        ],
                    ],
                    'options' => [
                        'urlParams' => [
                            'categoryId' => 101,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
            'invalid category id in parameters' => [
                'parameters' => [
                    'categoryId' => -101,
                ],
                'categoryId' => self::CATEGORY_ID,
                'includeSubcategories' => true,
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => true,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => true,
                        ],
                    ],
                ],
            ],
            'category exists' => [
                'parameters' => [],
                'categoryId' => self::CATEGORY_ID,
                'includeSubcategories' => true,
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => true,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider preBuildWithContentVariantIdDataProvider
     */
    public function testPreBuildWithContentVariantId(
        array $parameters,
        array $expectedParameters,
        array $expectedConfig
    ): void {
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryContentVariantId')
            ->willReturn(0);
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(0);
        $this->requestProductHandler->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->with($parameters['includeSubcategories'])
            ->willReturn($parameters['includeSubcategories']);

        $event = new PreBuild($this->config, new ParameterBag($parameters));
        $this->listener->onPreBuild($event);

        $this->assertEquals($expectedParameters, $event->getParameters()->all());
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function preBuildWithContentVariantIdDataProvider(): array
    {
        $filters = [
            'columns' => [
                'some_filter' => [
                    0 => 'options',
                ],
                'subcategory' => [
                    'data_name' => 'category_paths',
                    'label' => 'oro.catalog.filter.subcategory.label',
                    'type' => 'subcategory',
                    'rootCategory' => $this->createCategory(self::CATEGORY_ID, '1_42'),
                    'options' => [
                        'choices' => [
                            $this->createCategory(1001, '1_42_1001'),
                            $this->createCategory(2002, '1_42_2002'),
                        ],
                    ],
                ],
            ],
            'default' => [
                'some_filter' => [
                    0 => 'defaults',
                ],
            ],
        ];

        return [
            'content variant not of expected type' => [
                'parameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                    'categoryContentVariantId' => self::CONTENT_VARIANT_OTHER_TYPE_ID,
                    'overrideVariantConfiguration' => false,
                ],
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                    'categoryContentVariantId' => self::CONTENT_VARIANT_OTHER_TYPE_ID,
                    'overrideVariantConfiguration' => false,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
            'content variant not exists' => [
                'parameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                    'categoryContentVariantId' => 100,
                    'overrideVariantConfiguration' => false,
                ],
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                    'categoryContentVariantId' => 100,
                    'overrideVariantConfiguration' => false,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
            'content variant exists' => [
                'parameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                    'categoryContentVariantId' => self::CONTENT_VARIANT_ID,
                    'overrideVariantConfiguration' => true,
                ],
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                    'categoryContentVariantId' => self::CONTENT_VARIANT_ID,
                    'overrideVariantConfiguration' => true,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryContentVariantId' => self::CONTENT_VARIANT_ID,
                            'overrideVariantConfiguration' => true,
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider preBuildWithContentVariantIdInRequestDataProvider
     */
    public function testPreBuildWithContentVariantIdInRequest(
        array $parameters,
        int $contentVariantId,
        bool $overrideVariantConfiguration,
        array $expectedParameters,
        array $expectedConfig
    ): void {
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(self::CATEGORY_ID);
        $this->requestProductHandler->expects($this->once())
            ->method('getCategoryContentVariantId')
            ->willReturn($contentVariantId);
        $this->requestProductHandler->expects($this->any())
            ->method('getOverrideVariantConfiguration')
            ->willReturn($overrideVariantConfiguration);

        $event = new PreBuild($this->config, new ParameterBag($parameters));
        $this->listener->onPreBuild($event);

        $this->assertEquals($expectedParameters, $event->getParameters()->all());
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function preBuildWithContentVariantIdInRequestDataProvider(): array
    {
        $filters = [
            'columns' => [
                'some_filter' => [
                    0 => 'options',
                ],
                'subcategory' => [
                    'data_name' => 'category_paths',
                    'label' => 'oro.catalog.filter.subcategory.label',
                    'type' => 'subcategory',
                    'rootCategory' => $this->createCategory(self::CATEGORY_ID, '1_42'),
                    'options' => [
                        'choices' => [
                            $this->createCategory(1001, '1_42_1001'),
                            $this->createCategory(2002, '1_42_2002'),
                        ],
                    ],
                ],
            ],
            'default' => [
                'some_filter' => [
                    0 => 'defaults',
                ],
            ],
        ];

        return [
            'content variant not of expected type' => [
                'parameters' => [],
                'categoryContentVariantId' => self::CONTENT_VARIANT_OTHER_TYPE_ID,
                'overrideVariantConfiguration' => false,
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
            'content variant not exists' => [
                'parameters' => [],
                'categoryContentVariantId' => 100,
                'overrideVariantConfiguration' => false,
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
            'invalid content variant id in parameters' => [
                'parameters' => [
                    'categoryContentVariantId' => -101,
                    'categoryId' => self::CATEGORY_ID,
                ],
                'categoryContentVariantId' => 100,
                'overrideVariantConfiguration' => true,
                'expectedParameters' => [
                    'categoryContentVariantId' => -101,
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
            'content variant exists' => [
                'parameters' => [],
                'categoryContentVariantId' => self::CONTENT_VARIANT_ID,
                'overrideVariantConfiguration' => true,
                'expectedParameters' => [
                    'categoryId' => self::CATEGORY_ID,
                    'includeSubcategories' => false,
                    'categoryContentVariantId' => self::CONTENT_VARIANT_ID,
                    'overrideVariantConfiguration' => true,
                ],
                'expectedConfig' => [
                    'filters' => $filters,
                    'options' => [
                        'urlParams' => [
                            'categoryContentVariantId' => self::CONTENT_VARIANT_ID,
                            'overrideVariantConfiguration' => true,
                            'categoryId' => self::CATEGORY_ID,
                            'includeSubcategories' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testOnBuildAfterWithSingleCategory()
    {
        $this->requestProductHandler->expects($this->any())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(false);

        $datasource = $this->createMock(SearchDatasource::class);
        $query = $this->createMock(Query::class);
        $websiteSearchQuery = $this->createMock(WebsiteSearchQuery::class);

        $websiteSearchQuery->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $expr = Criteria::expr()->eq('text.category_path', $this->category->getMaterializedPath());

        $websiteSearchQuery->expects($this->once())
            ->method('addWhere')
            ->with($expr);

        $parameters = new ParameterBag(['categoryId' => self::CATEGORY_ID]);

        $dataGrid = new Datagrid('test', $this->config, $parameters);
        $dataGrid->setDatasource($datasource);

        $datasource->expects($this->any())
            ->method('getSearchQuery')
            ->willReturn($websiteSearchQuery);

        $event = new BuildAfter($dataGrid);
        $this->listener->onBuildAfter($event);

        $this->assertEquals(['categoryId' => self::CATEGORY_ID], $event->getDatagrid()->getParameters()->all());

        $expectedProperties = [
            'properties' => [
                'view_link' => [
                    'direct_params' => [
                        SluggableUrlGenerator::CONTEXT_TYPE => 'category',
                        SluggableUrlGenerator::CONTEXT_DATA => self::CATEGORY_ID,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedProperties, $event->getDatagrid()->getConfig()->toArray(['properties']));
    }

    public function testOnBuildAfterWithIncludeSubcategories()
    {
        $datasource = $this->createMock(SearchDatasource::class);

        $websiteSearchQuery = $this->createMock(WebsiteSearchQuery::class);
        $websiteSearchQuery->expects($this->never())
            ->method($this->anything());

        $this->requestProductHandler->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(true);

        $parameters = new ParameterBag(['categoryId' => self::CATEGORY_ID, 'includeSubcategories' => true]);
        $dataGrid = new Datagrid('test', $this->config, $parameters);
        $dataGrid->setDatasource($datasource);

        $datasource->expects($this->any())
            ->method('getSearchQuery')
            ->willReturn($websiteSearchQuery);

        $event = new BuildAfter($dataGrid);
        $this->listener->onBuildAfter($event);

        $expectedProperties = [
            'properties' => [
                'view_link' => [
                    'direct_params' => [
                        SluggableUrlGenerator::CONTEXT_TYPE => 'category',
                        SluggableUrlGenerator::CONTEXT_DATA => self::CATEGORY_ID,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedProperties, $event->getDatagrid()->getConfig()->toArray(['properties']));
    }

    private function createCategory(int $id, string $path): Category
    {
        return (new CategoryStub())
            ->setId($id)
            ->setMaterializedPath($path)
            ->setCreatedAt(new \DateTime('01 Jan 2021'))
            ->setUpdatedAt(new \DateTime('01 Jan 2021'));
    }
}
