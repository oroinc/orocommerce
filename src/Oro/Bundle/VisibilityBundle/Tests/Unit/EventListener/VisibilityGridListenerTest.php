<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\EventListener\VisibilityGridListener;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;

class VisibilityGridListenerTest extends \PHPUnit_Framework_TestCase
{
    const ACCOUNT_CATEGORY_VISIBILITY_GRID = 'account-category-visibility-grid';
    const ACCOUNT_GROUP_PRODUCT_VISIBILITY_GRID = 'account-group-product-visibility-grid';

    const COLUMNS_PATH = '[columns][visibility]';
    const FILTERS_PATH = '[filters][columns][visibility][options][field_options]';
    const SELECTOR_PATH = '[options][cellSelection][selector]';
    const SCOPE_PATH = '[scope]';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|VisibilityChoicesProvider
     */
    protected $visibilityChoicesProvider;

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

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->visibilityChoicesProvider = $this
            ->getMockBuilder(VisibilityChoicesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->visibilityChoicesProvider->expects($this->any())
            ->method('getFormattedChoices')
            ->willReturn($this->choices);

        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new VisibilityGridListener(
            $this->registry,
            $this->visibilityChoicesProvider,
            $this->scopeManager
        );

        $this->listener->addSubscribedGridConfig(
            self::ACCOUNT_CATEGORY_VISIBILITY_GRID,
            'account',
            AccountCategoryVisibility::class,
            Category::class
        );

        $this->listener->addSubscribedGridConfig(
            self::ACCOUNT_GROUP_PRODUCT_VISIBILITY_GRID,
            'accountGroup',
            AccountGroupProductVisibility::class,
            Product::class
        );
    }

    public function testOnPreBuild()
    {
        $repository = $this->getMock(ObjectRepository::class);
        $repository->method('find')->with(1)->willReturn(new Category());
        $this->registry->method('getRepository')->with(Category::class)->willReturn($repository);

        $config = $this->getMockBuilder(DatagridConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->method('getName')->willReturn(self::ACCOUNT_CATEGORY_VISIBILITY_GRID);
        $config->method('offsetGetByPath')
            ->willReturnMap(
                [
                    ['[options][cellSelection][selector]', null, '#account-category-visibility-changeset'],
                    ['[scope]', null, 'scope'],
                    ['[columns][visibility]', null, []],
                    ['[filters][columns][visibility][options][field_options]', null, []],
                ]
            );

        // assert that grid configuration was modified
        $config->expects($this->exactly(4))
            ->method('offsetSetByPath')
            ->withConsecutive(
                ['[options][cellSelection][selector]', '#account-category-visibility-changeset-2'],
                ['[scope]', 'scope-2'],
                ['[columns][visibility]', ['choices' => $this->choices]],
                ['[filters][columns][visibility][options][field_options]', ['choices' => $this->choices]]
            );

        $event = new PreBuild($config, new ParameterBag(['target_entity_id' => 1, 'scope_id' => 2]));
        $this->listener->onPreBuild($event);
    }

    public function testOnOrmResultBeforeQuery()
    {
        $scope = new Scope();
        $repository = $this->getMock(ObjectRepository::class);
        $repository->method('find')->with(1)->willReturn($scope);
        $this->registry->method('getRepository')->with(Scope::class)->willReturn($repository);

        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())->method('getCriteriaByScope')
            ->with($scope, 'account_category_visibility')
            ->willReturn($scopeCriteria);

        $datagrid = $this->getMock(DatagridInterface::class);
        $datagrid->method('getParameters')->willReturn(new ParameterBag(['scope_id' => 1]));
        $datagrid->method('getName')->willReturn(self::ACCOUNT_CATEGORY_VISIBILITY_GRID);
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeCriteria->expects($this->once())->method('applyToJoin')
            ->with($qb, 'scope', ['account']);

        $event = new OrmResultBeforeQuery($datagrid, $qb);
        $this->listener->onOrmResultBeforeQuery($event);
    }

    public function testOnOrmResultBeforeQueryDefaultScope()
    {
        $scope = new Scope();
        $this->scopeManager->method('findDefaultScope')->willReturn($scope);
        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())->method('getCriteriaByScope')
            ->with($scope, 'account_category_visibility')
            ->willReturn($scopeCriteria);

        $datagrid = $this->getMock(DatagridInterface::class);
        $datagrid->method('getParameters')->willReturn(new ParameterBag([]));
        $datagrid->method('getName')->willReturn(self::ACCOUNT_CATEGORY_VISIBILITY_GRID);
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeCriteria->expects($this->once())->method('applyToJoin')
            ->with($qb, 'scope', ['account']);

        $event = new OrmResultBeforeQuery($datagrid, $qb);
        $this->listener->onOrmResultBeforeQuery($event);
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
            ->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dataSource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $dataGrid = $this->getMock(DatagridInterface::class);
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

        $repository = $this->getMock(ObjectRepository::class);
        $repository->expects($this->exactly(1))
            ->method('find')
            ->willReturnMap(
                [
                    [1, null, null, new Category()],
                ]
            );
        $this->registry->expects($this->exactly(1))
            ->method('getRepository')
            ->with(Category::class)
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
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();

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
