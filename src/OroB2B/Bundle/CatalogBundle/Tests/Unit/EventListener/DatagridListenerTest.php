<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\EventListener\DatasourceBindParametersListener;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\CatalogBundle\EventListener\DatagridListener;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const DATA_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';
    const QUERY_AND_PATH = '[source][query][where][and]';
    const OPTIONS_CATEGORY = '[options][urlParams][categoryId]';
    const OPTIONS_SUBCATEGORY = '[options][urlParams][includeSubcategories]';
    const CATEGORY_ID_ALIAS = 'productCategoryIds';

    /**
     * @var array
     */
    protected static $expectedTemplate = [
        'source' => [
            'query' => [
                'join' => [
                    'left' => [
                        [
                            'join' => 'OroCatalogBundle:Category',
                            'alias' => 'productCategory',
                            'conditionType' => 'WITH',
                            'condition' => 'product MEMBER OF productCategory.products'
                        ],
                    ]
                ],
            ],
        ],
        'columns' => [
            DatagridListener::CATEGORY_COLUMN => [
                'label' => 'oro.catalog.category.entity_label'
            ]
        ],
        'properties' => [
            DatagridListener::CATEGORY_COLUMN => [
                'type' => LocalizedValueProperty::NAME,
                'data_name' => 'productCategory.titles',
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

    /** @var  ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestProductHandler;

    /** @var  DatagridListener */
    protected $listener;

    public function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
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

        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
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
            [sprintf('productCategory.id IN (:%s)', self::CATEGORY_ID_ALIAS)],
            $event->getConfig()->offsetGetByPath(self::QUERY_AND_PATH)
        );
        $this->assertEquals(
            [self::CATEGORY_ID_ALIAS],
            $event->getConfig()->offsetGetByPath(DatasourceBindParametersListener::DATASOURCE_BIND_PARAMETERS_PATH)
        );

        $this->assertEquals(
            $catId,
            $event->getConfig()->offsetGetByPath(self::OPTIONS_CATEGORY)
        );
        $this->assertEquals(
            $includeSubcategoriesChoice,
            $event->getConfig()->offsetGetByPath(self::OPTIONS_SUBCATEGORY)
        );

        $this->listener->onPreBuildProducts($event);
        $this->assertCount(1, $event->getConfig()->offsetGetByPath('[source][query][join][left]'));
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
        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
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

        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn(false);
        $this->doctrine->expects($this->never())->method('getRepository');
        $this->listener->onPreBuildProducts($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\DataGridBundle\Event\PreBuild
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
