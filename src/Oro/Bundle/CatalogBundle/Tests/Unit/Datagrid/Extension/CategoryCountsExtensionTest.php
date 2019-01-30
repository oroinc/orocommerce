<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Datagrid\Cache\CategoryCountsCache;
use Oro\Bundle\CatalogBundle\Datagrid\Extension\CategoryCountsExtension;
use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\ElasticSearchBundle\Engine\ElasticSearch;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
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

    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchQuery;

    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchQueryWithoutFilters;

    /** @var DatagridParametersHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridParametersHelper;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var CategoryCountsExtension */
    private $extension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->datagridManager = $this->createMock(Manager::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->productSearchRepository = $this->createMock(ProductRepository::class);
        $this->cache = $this->createMock(CategoryCountsCache::class);
        $this->searchQuery = $this->createMock(SearchQueryInterface::class);
        $this->searchQueryWithoutFilters = $this->createMock(SearchQueryInterface::class);
        $this->datagridParametersHelper = $this->createMock(DatagridParametersHelper::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

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

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->extension = new CategoryCountsExtension(
            new ServiceLink($this->datagridManager),
            $registry,
            $this->productSearchRepository,
            $this->cache,
            $this->datagridParametersHelper,
            $this->featureChecker,
            ElasticSearch::ENGINE_NAME
        );
        $this->extension->setParameters(
            new ParameterBag(
                [
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
                ]
            )
        );
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

    public function testVisitMetadata(): void
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

        $this->datagridManager->expects($this->exactly(2))
            ->method('getDatagrid')
            ->with(self::GRID_NAME, $this->extension->getParameters())
            ->willReturn($this->datagrid);

        $this->productSearchRepository->expects($this->at(0))
            ->method('getCategoryCountsByCategory')
            ->with($category, $this->searchQuery)
            ->willReturn([1 => 2]);

        $this->productSearchRepository->expects($this->at(1))
            ->method('getCategoryCountsByCategory')
            ->with($category, $this->searchQueryWithoutFilters)
            ->willReturn([1 => 2, 2 => 3]);

        $key = 'grid1|{"_filter":{"filter1":[],"subcategory":{"value":[1,2,3]}},"categoryId":42}';

        $this->cache->expects($this->exactly(2))
            ->method('getCounts')
            ->with($key)
            ->willReturn(null);
        $this->cache->expects($this->exactly(2))
            ->method('setCounts')
            ->with($key);

        $this->datagridParametersHelper->expects($this->exactly(2))
            ->method('setDatagridExtensionSkipped')
            ->with($this->extension->getParameters());

        $this->datagridParametersHelper->expects($this->once())
            ->method('resetFilter')
            ->with($this->extension->getParameters(), SubcategoryFilter::FILTER_TYPE_NAME);
        $this->datagridParametersHelper->expects($this->once())
            ->method('resetFilters')
            ->with($this->extension->getParameters());

        $this->datagridParametersHelper->expects($this->exactly(2))
            ->method('getFromParameters')
            ->willReturn(null);
        $this->datagridParametersHelper->expects($this->exactly(2))
            ->method('getFromMinifiedParameters')
            ->willReturn([
                'filter1' => [],
                SubcategoryFilter::FILTER_TYPE_NAME => ['value' => [1, 2, 3]],
            ]);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
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
                    'disabledOptions' => [0 => '2']
                ]
            ]
        ];

        $this->assertEquals($metadataArray, $metadata->toArray());
    }

    /**
     * @dataProvider getAdditionalParameters
     *
     * @param array $additionalParameters
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

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
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
                    'disabledOptions' => [0 => '2']
                ]
            ]
        ];

        $this->assertEquals($metadataArray, $metadata->toArray());
    }

    /**
     * @dataProvider visitMetadataProvider
     *
     * @param array $parameters
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
        $this->extension->visitMetadata(DatagridConfiguration::create([]), $metadata);

        $this->assertEquals(
            [
                'filters' => [
                    [
                        'type' => 'filter1',
                    ],
                    [
                        'type' => SubcategoryFilter::FILTER_TYPE_NAME,
                        'counts' => [],
                        'disabledOptions' => []
                    ],
                ],
            ],
            $metadata->toArray()
        );
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
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
