<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\EventListener\VisibilityGridListener;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;

class VisibilityGridListenerTest extends \PHPUnit_Framework_TestCase
{
    const CATEGORY_VISIBILITY_CLASS = 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility';
    const PRODUCT_VISIBILITY_CLASS = 'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility';

    const CATEGORY_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';
    const PRODUCT_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';

    const ACCOUNT_CATEGORY_VISIBILITY_GRID = 'account-category-visibility-grid';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY_GRID = 'account-group-category-visibility-grid';

    const ACCOUNT_PRODUCT_VISIBILITY_GRID = 'account-product-visibility-grid';
    const ACCOUNT_GROUP_PRODUCT_VISIBILITY_GRID = 'account-group-product-visibility-grid';

    const COLUMNS_PATH = '[columns][visibility]';
    const FILTERS_PATH = '[filters][columns][visibility][options][field_options]';
    const SELECTOR_PATH = '[options][cellSelection][selector]';
    const SCOPE_PATH = '[scope]';
    
    const PRODUCT_ID = 123;
    const WEBSITE_ID = 42;
    
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|VisibilityChoicesProvider
     */
    protected $visibilityChoicesProvider;

    /**
     * @var string
     */
    protected $categoryClass;

    /**
     * @var VisibilityGridListener
     */
    protected $listener;

    /**
     * @var array
     */
    protected $choices = [
        'hidden' => 'Hidden',
        'visible' => 'Visible',
    ];

    protected function setUp()
    {
        $this->markTestSkipped('Should be fixed after BB-4710');
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->visibilityChoicesProvider = $this
            ->getMockBuilder('Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->visibilityChoicesProvider->expects($this->any())
            ->method('getFormattedChoices')
            ->willReturn($this->choices);

        $this->categoryClass = 'Oro\Bundle\CatalogBundle\Entity\Category';

        $this->listener = new VisibilityGridListener($this->registry, $this->visibilityChoicesProvider);
        $this->listener->addSubscribedGridConfig(
            self::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            self::CATEGORY_VISIBILITY_CLASS,
            self::CATEGORY_CLASS
        );

        $this->listener->addSubscribedGridConfig(
            self::ACCOUNT_GROUP_PRODUCT_VISIBILITY_GRID,
            self::PRODUCT_VISIBILITY_CLASS,
            self::PRODUCT_CLASS
        );
    }

    public function testOnPreBuildWithCategory()
    {
        $rootCategory = new Category();
        $subCategory = (new Category())->setParentCategory($rootCategory);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap(
                [
                    [1, null, null, $rootCategory],
                    [2, null, null, $subCategory],
                ]
            );
        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->with($this->categoryClass)
            ->willReturn($repository);

        $this->listener->onPreBuild($this->getPreBuildWithCategory(1, $rootCategory));
        $this->listener->onPreBuild($this->getPreBuildWithCategory(2, $subCategory));
    }


    /**
     * @param int|null $categoryId
     * @param Category|null $category
     * @return \PHPUnit_Framework_MockObject_MockObject|PreBuild
     */
    protected function getPreBuildWithCategory($categoryId, $category)
    {
        $parameters = new ParameterBag();
        $parameters->set('target_entity_id', $categoryId);

        $pathConfig = [];

        /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('offsetGetByPath')
            ->willReturnMap(
                [
                    [self::COLUMNS_PATH, null, $pathConfig],
                    [self::FILTERS_PATH, null, $pathConfig],
                ]
            );
        $config->expects($this->exactly(2))
            ->method('offsetSetByPath')
            ->willReturnCallback(
                function ($path, $config) use ($category) {
                    $this->assertContains(
                        $path,
                        [VisibilityGridListenerTest::COLUMNS_PATH, VisibilityGridListenerTest::FILTERS_PATH]
                    );

                    $this->assertNotContains(
                        $path,
                        [VisibilityGridListenerTest::SCOPE_PATH, VisibilityGridListenerTest::SELECTOR_PATH]
                    );

                    $this->assertArrayHasKey('choices', $config);
                    $this->assertEquals($config['choices'], $this->choices);
                }
            );
        $config->expects($this->once())->method('getName')->willReturn(self::ACCOUNT_CATEGORY_VISIBILITY_GRID);

        return $this->getPreBuildEvent($config, $parameters);
    }

    public function testOnPreBuildWithProduct()
    {
        $parameters = new ParameterBag();
        $product = new Product();

        $parameters->set('target_entity_id', self::PRODUCT_ID);
        $parameters->set('website_id', self::WEBSITE_ID);

        /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())->method('getName')->willReturn(self::ACCOUNT_GROUP_PRODUCT_VISIBILITY_GRID);

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($product);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::PRODUCT_CLASS)
            ->willReturn($repository);

        $selector = '#selector';
        $scope = 'website';

        $config->expects($this->exactly(4))
            ->method('offsetGetByPath')
            ->willReturnMap(
                [
                    [self::SELECTOR_PATH, null, $selector],
                    [self::SCOPE_PATH, null, $scope],
                    [self::COLUMNS_PATH, null, []],
                    [self::FILTERS_PATH, null, []],
                ]
            );

        $config->expects($this->exactly(4))
            ->method('offsetSetByPath')
            ->willReturnCallback(
                function ($path, $config) use ($selector, $scope, $product) {
                    $this->assertContains(
                        $path,
                        [
                            VisibilityGridListenerTest::SELECTOR_PATH,
                            VisibilityGridListenerTest::SCOPE_PATH,
                            VisibilityGridListenerTest::COLUMNS_PATH,
                            VisibilityGridListenerTest::FILTERS_PATH,
                        ]
                    );
                    $this->assertContains(
                        $config,
                        [
                            sprintf(
                                '%s-%d',
                                $selector,
                                self::WEBSITE_ID
                            ),
                            sprintf(
                                '%s-%d',
                                $scope,
                                self::WEBSITE_ID
                            ),
                            [
                                'choices' => $this->visibilityChoicesProvider->getFormattedChoices(
                                    VisibilityGridListenerTest::PRODUCT_VISIBILITY_CLASS,
                                    $product
                                ),
                            ],
                        ]
                    );
                }
            );
        $event = $this->getPreBuildEvent($config, $parameters);
        $this->listener->onPreBuild($event);
    }

    public function testOnResultBefore()
    {
        $event = $this->getOrmResultBeforeEvent(
            self::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag(AccountCategoryVisibility::getDefault(new Category()))
        );

        $expected = (string)(new Expr())->orX(
            (new Expr())->isNull(VisibilityGridListener::VISIBILITY_FIELD)
        );

        $this->listener->onResultBefore($event);
        $this->assertStringEndsWith($expected, $event->getQuery()->getDQL());
    }

    public function testOnResultBeforeNotFilteredByDefault()
    {
        $event = $this->getOrmResultBeforeEvent(
            self::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag($this->getNotDefaultAccountCategoryVisibility())
        );
        $this->listener->onResultBefore($event);
        $this->assertNull($event->getQuery()->getDQL());
    }

    public function testOnResultBeforeNoFilter()
    {
        $event = $this->getOrmResultBeforeEvent(
            self::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag()
        );
        $this->listener->onResultBefore($event);
        $this->assertNull($event->getQuery()->getDQL());
    }

    /**
     * @param string $gridName
     * @param ParameterBag $bag
     *
     * @return OrmResultBefore
     */
    protected function getOrmResultBeforeEvent($gridName, ParameterBag $bag)
    {
        return new OrmResultBefore(
            $this->getDatagrid($gridName, $bag),
            new Query($this->getEntityManager())
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param ParameterBag $parameters
     * @return PreBuild|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPreBuildEvent(DatagridConfiguration $config, ParameterBag $parameters)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|PreBuild $preBuild */
        $preBuild = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\PreBuild')
            ->disableOriginalConstructor()
            ->getMock();
        $preBuild->expects($this->exactly(1))
            ->method('getConfig')
            ->willReturn($config);
        $preBuild->expects($this->exactly(1))
            ->method('getParameters')
            ->willReturn($parameters);

        return $preBuild;
    }

    /**
     * @param string $gridName
     * @param ParameterBag $bag
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridInterface
     */
    protected function getDatagrid($gridName, ParameterBag $bag)
    {
        $qb = new QueryBuilder($this->getEntityManager());
        $qb->where(sprintf("%s IN(1)", VisibilityGridListener::VISIBILITY_FIELD));

        /** @var OrmDatasource|\PHPUnit_Framework_MockObject_MockObject $dataSource */
        $dataSource = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $dataGrid
            ->expects($this->any())
            ->method('getName')
            ->willReturn($gridName);
        $dataGrid
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn($bag);
        $dataGrid->expects($this->any())
            ->method('getDataSource')
            ->willReturn($dataSource);

        return $dataGrid;
    }

    /**
     * @param string|null $visibilityFilterValue
     *
     * @return ParameterBag
     */
    protected function getParameterBag($visibilityFilterValue = null)
    {
        $bag = new ParameterBag();

        if (!$visibilityFilterValue) {
            return $bag;
        }

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->exactly(1))
            ->method('find')
            ->willReturnMap(
                [
                    [1, null, null, new Category()],
                ]
            );
        $this->registry->expects($this->exactly(1))
            ->method('getRepository')
            ->with($this->categoryClass)
            ->willReturn($repository);

        $bag->set(
            '_filter',
            [
                'visibility' => [
                    'value' => [
                        $visibilityFilterValue,
                    ],
                ],
            ]
        );
        $bag->set('target_entity_id', 1);

        return $bag;
    }

    /**
     * @return EntityManagerMock
     */
    protected function getEntityManager()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();

        return EntityManagerMock::create($connection);
    }

    /**
     * @return string|null
     */
    protected function getNotDefaultAccountCategoryVisibility()
    {
        $category = new Category();
        foreach (AccountCategoryVisibility::getVisibilityList($category) as $visibility) {
            if (AccountCategoryVisibility::getDefault($category) != $visibility) {
                return $visibility;
            }
        };
        return null;
    }
}
