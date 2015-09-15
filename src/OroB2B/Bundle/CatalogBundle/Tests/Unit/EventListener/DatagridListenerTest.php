<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\EventListener\DatasourceBindParametersListener;

use OroB2B\Bundle\CatalogBundle\EventListener\DatagridListener;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\CatalogBundle\Entity\Category';
    const QUERY_AND_PATH = '[source][query][where][and]';
    const CATEGORY_ID_ALIAS = 'productCategoryIds';

    /**
     * @var array
     */
    protected static $expectedTemplate = [
        'source' => [
            'query' => [
                'select' => [],
                'join' => [
                    'left' => [
                        [
                            'join' => 'OroB2BCatalogBundle:Category',
                            'alias' => 'productCategory',
                            'conditionType' => 'WITH',
                            'condition' => 'product MEMBER OF productCategory.products'
                        ],
                        [
                            'join' => 'productCategory.titles',
                            'alias' => 'categoryTitle'
                        ]
                    ]
                ],
                'where' => [
                    'and' => ['categoryTitle.locale IS NULL']
                ]
            ],
        ],
        'columns' => [
            DatagridListener::CATEGORY_COLUMN => [
                'label' => 'orob2b.catalog.category.entity_label'
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
                    'data_name' => 'categoryTitle.string'
                ]
            ],
        ]
    ];

    /** @var  Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestProductHandler;

    /** @var  DatagridListener */
    protected $listener;

    public function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestProductHandler = $this->getMock('OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler');
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

        $expected = self::$expectedTemplate;
        $expected['source']['query']['select'] = ['categoryTitle.string as '.DatagridListener::CATEGORY_COLUMN];

        $this->assertEquals($expected, $config->toArray());
    }

    /**
     * @dataProvider childrenIdsDataProvider
     *
     * @param int $catId
     * @param boolean $includeSubcategoriesChoice
     * @param array $childrenIds
     * @param array $expectedParameters
     */
    public function testOnPreBuild(
        $catId,
        $includeSubcategoriesChoice,
        array $childrenIds,
        array $expectedParameters = []
    ) {
        $category = new Category();
        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn($catId);
        $this->requestProductHandler
            ->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn($includeSubcategoriesChoice);

        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())->method('find')->with($catId)->willReturn($category);
        $repo->expects($includeSubcategoriesChoice ? $this->once() : $this->never())
            ->method('getChildrenIds')->with($category)->willReturn($childrenIds);
        $this->doctrine->expects($this->once())->method('getRepository')->with(self::DATA_CLASS)->willReturn($repo);

        $event = $this->createPreBuildEvent();
        /** @var $config DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())->method('getConfig')->willReturn($config);

        $params = new ParameterBag();
        $event->expects($this->any())->method('getParameters')->willReturn($params);

        $config->expects($this->at(0))
            ->method('offsetSetByPath')
            ->with(self::QUERY_AND_PATH, [sprintf('productCategory.id IN (:%s)', self::CATEGORY_ID_ALIAS)]);
        $config->expects($this->at(1))
            ->method('offsetSetByPath')
            ->with(DatasourceBindParametersListener::DATASOURCE_BIND_PARAMETERS_PATH, [self::CATEGORY_ID_ALIAS]);

        $this->listener->onPreBuildProducts($event);
        $this->assertEquals($expectedParameters, $params->all());
    }

    /**
     * @return array
     */
    public function childrenIdsDataProvider()
    {
        return [
            [
                'catId' => 1,
                'includeSubcategories' => true,
                'childrenIds' => [2, 3],
                'expectedParameters' => ['productCategoryIds' => [2, 3, 1]]
            ],
            [
                'catId' => 1,
                'includeSubcategories' => true,
                'childrenIds' => [],
                'expectedParameters' => ['productCategoryIds' => [1]]
            ],
            [
                'catId' => 1,
                'includeSubcategories' => false,
                'childrenIds' => [],
                'expectedParameters' => ['productCategoryIds' => [1]]
            ],
        ];
    }

    public function testOnPreBuildWithoutExistingCategory()
    {
        $catId = 1;
        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn($catId);
        /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())->method('find')->with($catId)->willReturn(null);
        $this->doctrine->expects($this->once())->method('getRepository')->with(self::DATA_CLASS)->willReturn($repo);
        $repo->expects($this->never())->method('getChildrenIds');
        $event = $this->createPreBuildEvent();
        /** @var $config DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())->method('getConfig')->willReturn($config);

        $this->listener->onPreBuildProducts($event);
    }

    public function testOnPreBuildWithoutCategoryId()
    {
        $event = $this->createPreBuildEvent();
        /** @var $config DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())->method('getConfig')->willReturn($config);

        $this->requestProductHandler->expects($this->once())->method('getCategoryId')->willReturn(false);
        $this->doctrine->expects($this->never())->method('getRepository');
        $this->listener->onPreBuildProducts($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\DataGridBundle\Event\PreBuild
     */
    protected function createPreBuildEvent()
    {
        return $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\PreBuild')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
