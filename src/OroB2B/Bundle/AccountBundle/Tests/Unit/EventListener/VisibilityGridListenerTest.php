<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\EventListener\VisibilityGridListener;
use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class VisibilityGridListenerTest extends \PHPUnit_Framework_TestCase
{

    const VISIBILITY_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility';
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var VisibilityChoicesProvider
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

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->visibilityChoicesProvider = new VisibilityChoicesProvider($translator);
        $this->categoryClass = 'OroB2B\Bundle\CatalogBundle\Entity\Category';

        $this->listener = new VisibilityGridListener($this->registry, $this->visibilityChoicesProvider);
    }

    public function testOnPreBuild()
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


        $this->listener->onPreBuild($this->getPreBuild(null, null));
        $this->listener->onPreBuild($this->getPreBuild(1, $rootCategory));
        $this->listener->onPreBuild($this->getPreBuild(2, $subCategory));
    }

    /**
     * @param int|null $categoryId
     * @param Category|null $category
     * @return \PHPUnit_Framework_MockObject_MockObject|PreBuild
     */
    protected function getPreBuild($categoryId, $category)
    {
        $parameters = new ParameterBag();
        $parameters->set('category_id', $categoryId);

        $columnsPath = '[columns][visibility]';
        $filtersPath = '[filters][columns][visibility][options][field_options]';
        $targetClassPath = '[options][visibilityTarget]';

        $pathConfig = [];


        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('offsetGetByPath')
            ->willReturnMap(
                [
                    [$targetClassPath, null, self::VISIBILITY_CLASS],
                    [$columnsPath, null, $pathConfig],
                    [$filtersPath, null, $pathConfig],
                ]
            );
        $config->expects($this->exactly(2))
            ->method('offsetSetByPath')
            ->willReturnCallback(
                function ($path, $config) use ($category, $columnsPath, $filtersPath) {
                    $this->assertTrue(in_array($path, [$columnsPath, $filtersPath]));
                    $this->assertArrayHasKey('choices', $config);
                    if ($category && !$category->getParentCategory()) {
                        $this->assertArrayNotHasKey(CategoryVisibility::PARENT_CATEGORY, $config['choices']);
                    } else {
                        $this->assertArrayHasKey(CategoryVisibility::PARENT_CATEGORY, $config['choices']);
                    }
                }
            );

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

    public function testOnResultBefore()
    {
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag(AccountCategoryVisibility::PARENT_CATEGORY)
        );

        $expected = (string)(new Expr())->orX(
            (new Expr())->isNull(CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_ALIAS)
        );
        $this->listener->onResultBefore($event);

        $this->assertStringEndsWith($expected, $event->getQuery()->getDQL());
    }

    public function testOnResultBeforeForGroups()
    {
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag(AccountGroupCategoryVisibility::PARENT_CATEGORY)
        );

        $expected = (string)(new Expr())->orX(
            (new Expr())->isNull(CategoryVisibilityGridListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS)
        );
        $this->listener->onResultBefore($event);

        $this->assertStringEndsWith($expected, $event->getQuery()->getDQL());
    }

    public function testOnResultBeforeNotFilteredByDefault()
    {
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            $this->getParameterBag(AccountCategoryVisibility::CATEGORY)
        );
        $this->listener->onResultBefore($event);
        $this->assertNull($event->getQuery()->getDQL());
    }

    public function testOnResultBeforeNoFilter()
    {
        $event = $this->getOrmResultBeforeEvent(
            CategoryVisibilityGridListener::ACCOUNT_CATEGORY_VISIBILITY_GRID,
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
     * @param string $gridName
     * @param ParameterBag $bag
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridInterface
     */
    protected function getDatagrid($gridName, ParameterBag $bag)
    {
        $qb = new QueryBuilder($this->getEntityManager());
        $qb->where(sprintf("%s IN(1)", CategoryVisibilityGridListener::ACCOUNT_GROUP_CATEGORY_VISIBILITY_ALIAS));

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
}
