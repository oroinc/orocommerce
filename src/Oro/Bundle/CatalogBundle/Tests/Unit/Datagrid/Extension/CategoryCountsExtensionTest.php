<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
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
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\TestUtils\Mocks\ServiceLink;

class CategoryCountsExtensionTest extends \PHPUnit_Framework_TestCase
{
    const GRID_NAME = 'grid1';

    use EntityTrait;

    /** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
    protected $datagridManager;

    /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryRepository;

    /** @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $productSearchRepository;

    /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $datagrid;

    /** @var SearchQueryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $searchQuery;

    /** @var CategoryCountsExtension */
    protected $extension;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->datagridManager = $this->createMock(Manager::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->productSearchRepository = $this->createMock(ProductRepository::class);

        $this->searchQuery = $this->createMock(SearchQueryInterface::class);

        /** @var SearchDatasource|\PHPUnit_Framework_MockObject_MockObject $searchDatasource */
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

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->extension = new CategoryCountsExtension(
            new ServiceLink($this->datagridManager),
            $registry,
            $this->productSearchRepository
        );
        $this->extension->setParameters(new ParameterBag([]));
    }

    public function testIsApplicable()
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

    public function testIsApplicableSkipped()
    {
        $this->extension->addApplicableGrid(self::GRID_NAME);
        $this->extension->getParameters()->set(CategoryCountsExtension::SKIP_PARAM, true);

        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
            'source' => [
                'type' => SearchDatasource::TYPE,
            ],
        ]);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWithUnknownDatasource()
    {
        $this->extension->addApplicableGrid(self::GRID_NAME);

        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
        ]);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testIsApplicableWithUnknownGrid()
    {
        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
            'source' => [
                'type' => SearchDatasource::TYPE,
            ],
        ]);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testVisitMetadata()
    {
        $categoryId = 42;
        $includeSubcategories = true;

        $this->extension->getParameters()->add(
            [
                'categoryId' => $categoryId,
                'includeSubcategories' => $includeSubcategories,
                AbstractFilterExtension::FILTER_ROOT_PARAM => [
                    'filter1' => [],
                    SubcategoryFilter::FILTER_TYPE_NAME => [
                        'value' => [1, 2, 3]
                    ],
                ],
                ParameterBag::MINIFIED_PARAMETERS => [
                    AbstractFilterExtension::MINIFIED_FILTER_PARAM => [
                        'filter1' => [],
                        SubcategoryFilter::FILTER_TYPE_NAME => [
                            'value' => [4, 5, 6]
                        ],
                    ],
                ],
            ]
        );

        $expectedDatagridParameters = new ParameterBag([
            'categoryId' => $categoryId,
            'includeSubcategories' => $includeSubcategories,
            CategoryCountsExtension::SKIP_PARAM => true,
            AbstractFilterExtension::FILTER_ROOT_PARAM => [
                'filter1' => [],
            ],
            ParameterBag::MINIFIED_PARAMETERS => [
                AbstractFilterExtension::MINIFIED_FILTER_PARAM => [
                    'filter1' => [],
                ],
            ],
        ]);

        $category = new Category();

        $config = DatagridConfiguration::create([
            'name' => self::GRID_NAME,
            'options' => [
                'urlParams' => [
                    'categoryId' => $categoryId,
                ],
            ],
        ]);

        $this->categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $this->datagridManager->expects($this->once())
            ->method('getDatagrid')
            ->with(self::GRID_NAME, $expectedDatagridParameters)
            ->willReturn($this->datagrid);

        $this->productSearchRepository->expects($this->once())
            ->method('getCategoryCountsByCategory')
            ->with($category, $this->searchQuery)
            ->willReturn([1 => 2]);

        $metadata = MetadataObject::create([
            'filters' => [
                [
                    'type' => 'filter1',
                ],
                [
                    'type' => SubcategoryFilter::FILTER_TYPE_NAME,
                ],
            ],
        ]);

        $this->extension->visitMetadata($config, $metadata);

        $this->assertEquals(
            [
                'filters' => [
                    [
                        'type' => 'filter1',
                    ],
                    [
                        'type' => SubcategoryFilter::FILTER_TYPE_NAME,
                        'counts' => [
                            1 => 2,
                        ],
                    ],
                ],
            ],
            $metadata->toArray()
        );
    }

    /**
     * @dataProvider visitMetadataProvider
     *
     * @param array $parameters
     */
    public function testVisitMetadataWithoutCategory(array $parameters)
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

        $this->extension->getParameters()->add($parameters);
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
                    ],
                ],
            ],
            $metadata->toArray()
        );
    }

    /**
     * @return array
     */
    public function visitMetadataProvider()
    {
        return [
            'empty parameters' => [
                'parameters' => []
            ],
            'incorrect categoryId' => [
                'parameters' => [
                    'categoryId' => 'none',
                ]
            ],
            'negative categoryId' => [
                'parameters' => [
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
}
