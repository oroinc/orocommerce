<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\DatagridListener;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class DatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    const DATA_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';
    const QUERY_AND_PATH = '[source][query][where][and]';
    const QUERY_OR_PATH = '[source][query][where][or]';
    const OPTIONS_CATEGORY = '[options][urlParams][categoryId]';
    const OPTIONS_SUBCATEGORY = '[options][urlParams][includeSubcategories]';
    const OPTIONS_NOT_CATEGORIZED = '[options][urlParams][includeNotCategorizedProducts]';
    const CATEGORY_ID_ALIAS = 'productCategoryIds';

    /**
     * @var array
     */
    protected static $expectedTemplate = [
        'source' => [
            'query' => [
                'select' => [
                    'category.denormalizedDefaultTitle as ' . DatagridListener::CATEGORY_COLUMN
                ],
                'join' => [
                    'left' => [['join' => 'product.category', 'alias' => 'category']]
                ],
            ],
        ],
        'columns' => [
            DatagridListener::CATEGORY_COLUMN => [
                'label' => 'oro.catalog.category.entity_label',
                'data_name' => DatagridListener::CATEGORY_COLUMN

            ]
        ],
        'sorters' => [
            'columns' => [
                DatagridListener::CATEGORY_COLUMN => [
                    'data_name' => DatagridListener::CATEGORY_COLUMN
                ]
            ],
        ],
        'filters' => [
            'columns' => [
                DatagridListener::CATEGORY_COLUMN => [
                    'type' => 'string',
                    'data_name' => DatagridListener::CATEGORY_COLUMN
                ]
            ],
        ]
    ];

    /** @var  ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestProductHandler;

    /** @var  DatagridListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestProductHandler = $this
            ->getMockBuilder('Oro\Bundle\CatalogBundle\Handler\RequestProductHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new DatagridListener($this->doctrine, $this->requestProductHandler);
        $this->listener->setDataClass(self::DATA_CLASS);
    }

    public function testOnBuildBeforeProductsSelect()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);

        $this->listener->onBuildBeforeProductsSelect($event);

        $this->assertEquals(self::$expectedTemplate, $config->toArray());
    }

    /**
     * @dataProvider childrenIdsDataProvider
     *
     * @param boolean $useRequest
     * @param int $catId
     * @param boolean $includeSubcategoriesChoice
     * @param array $childrenIds
     * @param array $expectedParameters
     */
    public function testOnPreBuild(
        $useRequest,
        $catId,
        $includeSubcategoriesChoice,
        array $childrenIds,
        array $expectedParameters = []
    ) {
        $category = new Category();
        $event = $this->createPreBuildEvent();

        if ($useRequest) {
            $this->requestProductHandler->expects($this->atLeastOnce())->method('getCategoryId')->willReturn($catId);
            $this->requestProductHandler
                ->expects($this->atLeastOnce())
                ->method('getIncludeSubcategoriesChoice')
                ->willReturn($includeSubcategoriesChoice);
        } else {
            $event->getParameters()->set('categoryId', $catId);
            $event->getParameters()->set('includeSubcategories', $includeSubcategoriesChoice);
        }

        /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject $repo */
        $repo = $this->getMockBuilder('Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->atLeastOnce())->method('find')->with($catId)->willReturn($category);
        $repo->expects($includeSubcategoriesChoice ? $this->atLeastOnce() : $this->never())
            ->method('getChildrenIds')->with($category)->willReturn($childrenIds);
        $this->doctrine->expects($this->atLeastOnce())->method('getRepository')->with(self::DATA_CLASS)
            ->willReturn($repo);

        $this->listener->onPreBuildProducts($event);
        $this->assertEquals($expectedParameters, $event->getParameters()->all());

        $this->assertEquals(
            [sprintf('product.category IN (:%s)', self::CATEGORY_ID_ALIAS)],
            $event->getConfig()->offsetGetByPath(self::QUERY_AND_PATH)
        );
        $this->assertEquals(
            [self::CATEGORY_ID_ALIAS],
            $event->getConfig()->offsetGetByPath(DatagridConfiguration::DATASOURCE_BIND_PARAMETERS_PATH)
        );

        $this->assertEquals(
            $catId,
            $event->getConfig()->offsetGetByPath(self::OPTIONS_CATEGORY)
        );
        $this->assertEquals(
            $includeSubcategoriesChoice,
            $event->getConfig()->offsetGetByPath(self::OPTIONS_SUBCATEGORY)
        );
    }

    public function testOnPreBuildWithIncludeNotCategorizedProductsAndWithoutCategoryIdUserRequest()
    {
        $this->requestProductHandler->expects($this->atLeastOnce())
            ->method('getIncludeNotCategorizedProductsChoice')
            ->willReturn(true);

        $event = $this->createPreBuildEvent();
        $this->listener->onPreBuildProducts($event);

        $this->assertEquals(
            ['product.category IS NULL'],
            $event->getConfig()->offsetGetByPath(self::QUERY_OR_PATH)
        );

        $this->assertTrue($event->getConfig()->offsetGetByPath(self::OPTIONS_NOT_CATEGORIZED));
    }

    public function testOnPreBuildWithIncludeNotCategorizedProductsAndWithoutCategoryId()
    {
        $event = $this->createPreBuildEvent();
        $event->getParameters()->set('includeNotCategorizedProducts', true);

        $this->requestProductHandler->expects($this->never())
            ->method('getIncludeNotCategorizedProductsChoice');

        $this->listener->onPreBuildProducts($event);

        $this->assertEquals(
            ['product.category IS NULL'],
            $event->getConfig()->offsetGetByPath(self::QUERY_OR_PATH)
        );

        $this->assertTrue($event->getConfig()->offsetGetByPath(self::OPTIONS_NOT_CATEGORIZED));
    }

    public function testOnPreBuildWithIncludeNotCategorizedProductsAndWithCategoryId()
    {
        $category = new Category();
        $event = $this->createPreBuildEvent();
        $event->getParameters()->set('includeNotCategorizedProducts', true);
        $event->getParameters()->set('categoryId', 1);
        $event->getParameters()->set('includeSubcategories', true);

        $this->requestProductHandler->expects($this->never())
            ->method('getIncludeNotCategorizedProductsChoice');
        $this->requestProductHandler->expects($this->never())
            ->method('getCategoryId');
        $this->requestProductHandler->expects($this->never())
            ->method('getIncludeSubcategoriesChoice');

        $repo = $this->getMockBuilder('Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->atLeastOnce())->method('find')->with(1)->willReturn($category);
        $repo->expects($this->atLeastOnce())
            ->method('getChildrenIds')
            ->with($category)
            ->willReturn([2, 3]);

        $this->doctrine->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(self::DATA_CLASS)
            ->willReturn($repo);

        $this->listener->onPreBuildProducts($event);

        $this->assertEquals(
            [
                'productCategoryIds' => [2, 3, 1],
                'categoryId' => 1,
                'includeSubcategories' => true,
                'includeNotCategorizedProducts' => true
            ],
            $event->getParameters()->all()
        );

        $this->assertEquals(
            [sprintf('product.category IN (:%s)', self::CATEGORY_ID_ALIAS)],
            $event->getConfig()->offsetGetByPath(self::QUERY_AND_PATH)
        );
        $this->assertEquals(
            [self::CATEGORY_ID_ALIAS],
            $event->getConfig()->offsetGetByPath(DatagridConfiguration::DATASOURCE_BIND_PARAMETERS_PATH)
        );

        $this->assertEquals(1, $event->getConfig()->offsetGetByPath(self::OPTIONS_CATEGORY));
        $this->assertTrue($event->getConfig()->offsetGetByPath(self::OPTIONS_SUBCATEGORY));

        $this->assertEquals(
            ['product.category IS NULL'],
            $event->getConfig()->offsetGetByPath(self::QUERY_OR_PATH)
        );

        $this->assertTrue($event->getConfig()->offsetGetByPath(self::OPTIONS_NOT_CATEGORIZED));
    }

    /**
     * @return array
     */
    public function childrenIdsDataProvider()
    {
        return [
            [
                'useRequest' => true,
                'catId' => 1,
                'includeSubcategories' => true,
                'childrenIds' => [2, 3],
                'expectedParameters' => ['productCategoryIds' => [2, 3, 1]],
            ],
            [
                'useRequest' => true,
                'catId' => 1,
                'includeSubcategories' => true,
                'childrenIds' => [],
                'expectedParameters' => ['productCategoryIds' => [1]],
            ],
            [
                'useRequest' => true,
                'catId' => 1,
                'includeSubcategories' => false,
                'childrenIds' => [],
                'expectedParameters' => ['productCategoryIds' => [1]],
            ],
            [
                'useRequest' => false,
                'catId' => 2,
                'includeSubcategories' => true,
                'childrenIds' => [1],
                'expectedParameters' => [
                    'productCategoryIds' => [1, 2],
                    'categoryId' => 2,
                    'includeSubcategories' => true,
                ],
            ],
        ];
    }

    public function testOnPreBuildWithoutExistingCategory()
    {
        $catId = 1;
        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn($catId);
        /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject $repo */
        $repo = $this->getMockBuilder('Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())->method('find')->with($catId)->willReturn(null);
        $this->doctrine->expects($this->once())->method('getRepository')->with(self::DATA_CLASS)->willReturn($repo);
        $repo->expects($this->never())->method('getChildrenIds');
        $event = $this->createPreBuildEvent();

        $this->listener->onPreBuildProducts($event);
    }

    public function testOnPreBuildWithoutCategoryId()
    {
        $event = $this->createPreBuildEvent();

        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn(0);
        $this->doctrine->expects($this->never())->method('getRepository');
        $this->listener->onPreBuildProducts($event);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Oro\Bundle\DataGridBundle\Event\PreBuild
     */
    protected function createPreBuildEvent()
    {
        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\PreBuild')
            ->disableOriginalConstructor()
            ->getMock();
        $config = DatagridConfiguration::create([]);
        $event->expects($this->any())->method('getConfig')->willReturn($config);

        $params = new ParameterBag();
        $event->expects($this->any())->method('getParameters')->willReturn($params);

        return $event;
    }
}
