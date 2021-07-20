<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Datagrid\Cache\CategoryCountsCache;
use Oro\Bundle\CatalogBundle\Datagrid\Extension\CategoryCountsExtension;
use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\TestUtils\Mocks\ServiceLink;

class CategoryCountsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const CATEGORY_ID = 42;
    private const GRID_NAME = 'grid1';

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridManager;

    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepository;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productSearchRepository;

    /** @var CategoryCountsCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $datagridWithoutFilters;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
    private $registry;

    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchQuery;

    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchQueryWithoutFilters;

    /** @var DatagridParametersHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridParametersHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CategoryCountsExtension */
    private $extension;

    /** @var array  */
    private $parameters = [
        'categoryId' => self::CATEGORY_ID,
        'includeSubcategories' => true,
        AbstractFilterExtension::FILTER_ROOT_PARAM => [
            'filter1' => [],
            SubcategoryFilter::FILTER_TYPE_NAME => ['value' => [1, 2, 3]],
        ],
        ParameterBag::MINIFIED_PARAMETERS => [
            AbstractFilterExtension::MINIFIED_FILTER_PARAM => [
                'filter1' => [],
                SubcategoryFilter::FILTER_TYPE_NAME => ['value' => [4, 5, 6]],
            ],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->datagridManager = $this->createMock(Manager::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->productSearchRepository = $this->createMock(ProductRepository::class);
        $this->cache = $this->createMock(CategoryCountsCache::class);
        $this->searchQuery = $this->createMock(SearchQueryInterface::class);
        $this->searchQueryWithoutFilters = $this->createMock(SearchQueryInterface::class);
        $this->datagridParametersHelper = $this->createMock(DatagridParametersHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        /** @var SearchDatasource|\PHPUnit\Framework\MockObject\MockObject $searchDatasource */
        $searchDatasource = $this->createMock(SearchDatasource::class);
        $searchDatasource->expects($this->any())
            ->method('getSearchQuery')
            ->willReturn($this->searchQuery);

        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datagrid->expects($this->any())
            ->method('acceptDatasource')
            ->willReturnSelf();
        $this->datagrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn($searchDatasource);

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDatasource */
        $searchDatasourceWithoutFilters = $this->createMock(SearchDatasource::class);
        $searchDatasourceWithoutFilters->expects($this->any())
            ->method('getSearchQuery')
            ->willReturn($this->searchQueryWithoutFilters);

        $this->datagridWithoutFilters = $this->createMock(DatagridInterface::class);
        $this->datagridWithoutFilters->expects($this->any())
            ->method('acceptDatasource')
            ->willReturnSelf();
        $this->datagridWithoutFilters->expects($this->any())
            ->method('getDatasource')
            ->willReturn($searchDatasourceWithoutFilters);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->extension = new CategoryCountsExtension(
            new ServiceLink($this->datagridManager),
            $this->registry,
            $this->productSearchRepository,
            $this->cache,
            $this->datagridParametersHelper,
            $this->configManager
        );
        $this->extension->setParameters(new ParameterBag($this->parameters));
    }

    public function testIsApplicable(): void
    {
        $this->extension->addApplicableGrid(self::GRID_NAME);

        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
            'source' => [
                'type' => SearchDatasource::TYPE,
            ],
        ]);

        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsApplicableSkipped(): void
    {
        $this->extension->addApplicableGrid(self::GRID_NAME);
        $this->datagridParametersHelper->expects($this->once())
            ->method('isDatagridExtensionSkipped')
            ->with($this->extension->getParameters())
            ->willReturn(true);

        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
            'source' => [
                'type' => SearchDatasource::TYPE,
            ],
        ]);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWithUnknownDatasource(): void
    {
        $this->extension->addApplicableGrid(self::GRID_NAME);

        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
        ]);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWithUnknownGrid(): void
    {
        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
            'source' => [
                'type' => SearchDatasource::TYPE,
            ],
        ]);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testVisitMetadata(): void
    {
        $this->extension = new CategoryCountsExtension(
            new ServiceLink($this->datagridManager),
            $this->registry,
            $this->productSearchRepository,
            $this->cache,
            new DatagridParametersHelper(),
            $this->configManager
        );

        $this->extension->setParameters(new ParameterBag($this->parameters));

        $category = new Category();

        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
            'options' => [
                'urlParams' => ['categoryId' => self::CATEGORY_ID],
            ]
        ]);

        $this->categoryRepository->expects($this->any())
            ->method('find')
            ->with(self::CATEGORY_ID)
            ->willReturn($category);

        $parametersWithoutSubcategory = new ParameterBag([
            'categoryId' => self::CATEGORY_ID,
            'includeSubcategories' => true,
            '_filter' => [
                'filter1' => []
            ],
            '_minified' => [
                'f' => [
                    'filter1' => [],
                ]
            ],
            'dataGridSkipExtensionParam' => true
        ]);

        $parametersWithoutFilters = new ParameterBag([
            'categoryId' => self::CATEGORY_ID,
            'includeSubcategories' => true,
            '_filter' => [],
            '_minified' => [
                'f' => []
            ],
            'dataGridSkipExtensionParam' => true
        ]);

        $this->productSearchRepository->expects($this->at(0))
            ->method('getCategoryCountsByCategory')
            ->with($category, $this->searchQuery)
            ->willReturn([1 => 2]);

        $this->productSearchRepository->expects($this->at(1))
            ->method('getCategoryCountsByCategory')
            ->with($category, $this->searchQueryWithoutFilters)
            ->willReturn([1 => 2, 2 => 3]);

        $this->datagridManager->expects($this->at(0))
            ->method('getDatagrid')
            ->with(self::GRID_NAME, $parametersWithoutSubcategory)
            ->willReturn($this->datagrid);

        $this->datagridManager->expects($this->at(1))
            ->method('getDatagrid')
            ->with(self::GRID_NAME, $parametersWithoutFilters)
            ->willReturn($this->datagridWithoutFilters);

        $this->productSearchRepository->expects($this->at(0))
            ->method('getCategoryCountsByCategory')
            ->with($category, $this->searchQuery)
            ->willReturn([1 => 2]);

        $this->productSearchRepository->expects($this->at(1))
            ->method('getCategoryCountsByCategory')
            ->with($category, $this->searchQueryWithoutFilters)
            ->willReturn([1 => 2, 2 => 3]);

        $keyWithoutSubCategories
            = 'grid1|{"_filter":{"filter1":[]},"categoryId":42,"dataGridSkipExtensionParam":true}';
        $keyWithoutFilters = 'grid1|{"categoryId":42,"dataGridSkipExtensionParam":true}';

        $this->cache->expects($this->exactly(2))
            ->method('getCounts')
            ->willReturnMap([
                [$keyWithoutSubCategories, null],
                [$keyWithoutFilters, null]
            ]);

        $this->cache->expects($this->exactly(2))
            ->method('setCounts')
            ->withConsecutive(
                [$this->equalTo($keyWithoutSubCategories)],
                [$this->equalTo($keyWithoutFilters)]
            );

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturn(true);

        $metadataArray = [
            'filters' => [
                ['type' => 'filter1'],
                ['type' => SubcategoryFilter::FILTER_TYPE_NAME],
            ],
        ];
        $metadata = MetadataObject::create($metadataArray);

        $this->extension->visitMetadata($config, $metadata);

        $metadataArray = [
            'filters' => [
                0 => [
                    'type' => 'filter1'
                ],
                1 => [
                    'type' => 'subcategory',
                    'counts' => [1 => 2],
                    'countsWithoutFilters' => [1 => 2, 2 => 3],
                    'isDisableFiltersEnabled' => true
                ]
            ]
        ];

        $this->assertEquals($metadataArray, $metadata->toArray());
    }

    public function testVisitMetadataWhenExtensionAlreadyApplied(): void
    {
        $this->testVisitMetadata();

        $this->configManager
            ->expects(self::never())
            ->method('get');

        $filters = [['name' => 'supported_attribute']];
        $metadata = MetadataObject::create(['filters' => $filters]);
        $this->extension->visitMetadata(DatagridConfiguration::createNamed(self::GRID_NAME, []), $metadata);

        self::assertEquals($filters, $metadata->offsetGetByPath('[filters]'));
    }

    /**
     * @dataProvider getAdditionalParameters
     */
    public function testVisitMetadataFromCache(array $additionalParameters): void
    {
        $category = new Category();

        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
            'options' => [
                'urlParams' => ['categoryId' => self::CATEGORY_ID],
            ],
        ]);

        $this->categoryRepository->expects($this->exactly(2))
            ->method('find')
            ->with(self::CATEGORY_ID)
            ->willReturn($category);

        $this->datagridManager->expects($this->never())
            ->method('getDatagrid');

        $this->productSearchRepository->expects($this->never())
            ->method('getCategoryCountsByCategory');

        $key = 'grid1|{"_filter":{"filter1":[],"subcategory":{"value":[1,2,3]}},"categoryId":42}';

        $this->cache->expects($this->at(0))
            ->method('getCounts')
            ->with($key)
            ->willReturn([1 => 2]);

        $this->cache->expects($this->at(1))
            ->method('getCounts')
            ->with($key)
            ->willReturn([1 => 2, 2 => 3]);

        $this->cache->expects($this->never())
            ->method('setCounts');

        $this->datagridParametersHelper->expects($this->exactly(2))
            ->method('getFromParameters')
            ->willReturn([
                'filter1' => [],
                SubcategoryFilter::FILTER_TYPE_NAME => ['value' => [1, 2, 3]],
            ]);
        $this->datagridParametersHelper->expects($this->exactly(2))
            ->method('getFromMinifiedParameters')
            ->willReturn(null);

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturn(true);

        $commonParameters = $this->extension->getParameters()->all();
        $parameters = array_merge($commonParameters, $additionalParameters);
        $this->extension->setParameters(new ParameterBag($parameters));

        $metadataArray = [
            'filters' => [
                ['type' => 'filter1'],
                ['type' => SubcategoryFilter::FILTER_TYPE_NAME],
            ],
        ];
        $metadata = MetadataObject::create($metadataArray);

        $this->extension->visitMetadata($config, $metadata);

        $metadataArray = [
            'filters' => [
                0 => [
                    'type' => 'filter1'
                ],
                1 => [
                    'type' => 'subcategory',
                    'counts' => [1 => 2],
                    'countsWithoutFilters' => [1 => 2, 2 => 3],
                    'isDisableFiltersEnabled' => true
                ]
            ]
        ];

        $this->assertEquals($metadataArray, $metadata->toArray());
    }

    /**
     * @dataProvider visitMetadataProvider
     */
    public function testVisitMetadataWithoutCategory(array $parameters): void
    {
        $metadata = MetadataObject::create([
            'filters' => [
                ['type' => 'filter1'],
                ['type' => SubcategoryFilter::FILTER_TYPE_NAME],
            ],
        ]);

        $this->categoryRepository->expects($this->never())
            ->method($this->anything());

        $this->datagridManager->expects($this->never())
            ->method($this->anything());

        $this->productSearchRepository->expects($this->never())
            ->method($this->anything());

        $this->cache->expects($this->never())
            ->method($this->anything());

        $this->extension->setParameters(new ParameterBag($parameters));
        $this->extension->visitMetadata(DatagridConfiguration::createNamed(self::GRID_NAME, []), $metadata);

        $this->assertEquals(
            [
                'filters' => [
                    [
                        'type' => 'filter1',
                    ],
                    [
                        'type' => SubcategoryFilter::FILTER_TYPE_NAME,
                        'counts' => [],
                        'countsWithoutFilters' => [],
                        'isDisableFiltersEnabled' => false
                    ],
                ],
            ],
            $metadata->toArray()
        );
    }

    public function visitMetadataProvider(): array
    {
        return [
            'empty parameters' => [
                'parameters' => [
                    'includeSubcategories' => true
                ]
            ],
            'incorrect categoryId' => [
                'parameters' => [
                    'includeSubcategories' => true,
                    'categoryId' => 'none',
                ]
            ],
            'negative categoryId' => [
                'parameters' => [
                    'includeSubcategories' => true,
                    'categoryId' => -42,
                ]
            ],
            'incorrect includeSubcategories' => [
                'parameters' => [
                    'categoryId' => 42,
                    'includeSubcategories' => null
                ]
            ]
        ];
    }

    public function getAdditionalParameters(): array
    {
        return [
            'empty' => [
                []
            ],
            'categoryId as string' => [
                [
                    'categoryId' => '42'
                ]
            ],
            'pagination' => [
                [
                    '_pager' => [
                        '_page' => 2, '_per_page' => 10
                    ]
                ]
            ],
            'sorting' => [
                [
                    '_sort_by' => [ 'field' => 'ASC' ]
                ]
            ],
            'mixed' => [
                [
                    'originalRoute' => 'route_name',
                    'unknownParameter' => 'value'
                ]
            ]
        ];
    }
}
