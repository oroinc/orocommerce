<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\EventListener\VisibilityGridListener;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;

class VisibilityGridListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CUSTOMER_CATEGORY_VISIBILITY_GRID = 'customer-category-visibility-grid';

    private array $choices = [
        'hidden' => 'Hidden',
        'visible' => 'Visible',
    ];

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var VisibilityGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $visibilityChoicesProvider = $this->createMock(VisibilityChoicesProvider::class);
        $visibilityChoicesProvider->expects($this->any())
            ->method('getFormattedChoices')
            ->willReturn($this->choices);

        $this->listener = new VisibilityGridListener(
            $this->doctrine,
            $visibilityChoicesProvider,
            $this->scopeManager
        );
    }

    public function testOnPreBuild()
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturn(new Category());
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($repository);

        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects($this->any())
            ->method('getName')
            ->willReturn(self::CUSTOMER_CATEGORY_VISIBILITY_GRID);
        $config->expects($this->any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                ['[options][cellSelection][selector]', null, '#customer-category-visibility-changeset'],
                ['[options][visibilityEntityClass]', null, CustomerCategoryVisibility::class],
                ['[options][targetEntityClass]', null, Category::class],
                ['[scope]', null, 'scope'],
                ['[columns][visibility]', null, []],
                ['[filters][columns][visibility][options][field_options]', null, []],
            ]);

        // assert that grid configuration was modified
        $config->expects($this->exactly(4))
            ->method('offsetSetByPath')
            ->withConsecutive(
                ['[options][cellSelection][selector]', '#customer-category-visibility-changeset-2'],
                ['[scope]', 'scope-2'],
                ['[columns][visibility]', ['choices' => $this->choices]],
                ['[filters][columns][visibility][options][field_options]', ['choices' => $this->choices]]
            );

        $event = new PreBuild($config, new ParameterBag(['target_entity_id' => 1, 'scope_id' => 2]));
        $this->listener->onPreBuild($event);
    }

    public function testOnDatagridBuildAfter()
    {
        $scope = new Scope();
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturn($scope);
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Scope::class)
            ->willReturn($repository);

        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteriaByScope')
            ->with($scope, 'customer_category_visibility')
            ->willReturn($scopeCriteria);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ParameterBag(['scope_id' => 1]));
        $datagrid->expects($this->any())
            ->method('getName')
            ->willReturn(self::CUSTOMER_CATEGORY_VISIBILITY_GRID);
        $ds = $this->createMock(OrmDatasource::class);
        $qb = $this->createMock(QueryBuilder::class);
        $ds->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $datagrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn($ds);

        // assert that join with scope was applied properly
        $scopeCriteria->expects($this->once())
            ->method('applyToJoin')
            ->with($qb, 'scope', ['customer']);

        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->any())
            ->method('getName')
            ->willReturn(self::CUSTOMER_CATEGORY_VISIBILITY_GRID);
        $config->expects($this->any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                ['[options][scopeAttr]', null, 'customer'],
                ['[options][visibilityEntityClass]', null, CustomerCategoryVisibility::class],
            ]);
        $datagrid->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $event = new BuildAfter($datagrid);
        $this->listener->onDatagridBuildAfter($event);
    }

    public function testOnDatagridBuildAfterDefaultScope()
    {
        $scope = new Scope();
        $this->scopeManager->expects($this->any())
            ->method('findDefaultScope')
            ->willReturn($scope);
        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteriaByScope')
            ->with($scope, 'customer_category_visibility')
            ->willReturn($scopeCriteria);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ParameterBag([]));
        $datagrid->expects($this->any())
            ->method('getName')
            ->willReturn(self::CUSTOMER_CATEGORY_VISIBILITY_GRID);
        $ds = $this->createMock(OrmDatasource::class);
        $qb = $this->createMock(QueryBuilder::class);
        $ds->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $datagrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn($ds);

        // assert that join with scope was applied properly
        $scopeCriteria->expects($this->once())
            ->method('applyToJoin')
            ->with($qb, 'scope', ['customer']);

        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->any())
            ->method('getName')
            ->willReturn(self::CUSTOMER_CATEGORY_VISIBILITY_GRID);
        $config->expects($this->any())
            ->method('offsetGetByPath')
            ->willReturnMap([
                ['[options][scopeAttr]', null, 'customer'],
                ['[options][visibilityEntityClass]', null, CustomerCategoryVisibility::class],
            ]);
        $datagrid->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $event = new BuildAfter($datagrid);
        $this->listener->onDatagridBuildAfter($event);
    }
}
