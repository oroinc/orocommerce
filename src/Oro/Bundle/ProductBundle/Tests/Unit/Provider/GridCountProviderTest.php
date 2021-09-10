<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\ProductBundle\Provider\GridCountProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GridCountProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $gridManager;

    /**
     * @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authorizationChecker;

    /**
     * @var Pager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pager;

    /**
     * @var GridCountProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->gridManager = $this->createMock(ManagerInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->pager = $this->createMock(Pager::class);

        $this->provider = new GridCountProvider($this->gridManager, $this->authorizationChecker, $this->pager);
    }

    public function testAclException()
    {
        $gridName = 'test';

        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('getAclResource')
            ->willReturn('test_acl');
        $this->gridManager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->willReturn($config);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('test_acl')
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->provider->getGridCount($gridName);
    }

    public function testGetCountWithNonOrmDataSource()
    {
        $gridName = 'test';

        $this->assertConfigCalls();

        $dataSource = $this->createMock(DatasourceInterface::class);
        $this->assertDatasourceCalls($dataSource);

        $this->assertEquals(0, $this->provider->getGridCount($gridName));
    }

    public function testGetCountWithCustomCountQuery()
    {
        $gridName = 'test';

        $this->assertConfigCalls();

        $countQb = $this->createMock(QueryBuilder::class);
        $qb = $this->createMock(QueryBuilder::class);

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $dataSource->expects($this->once())
            ->method('getCountQb')
            ->willReturn($countQb);
        $dataSource->expects($this->once())
            ->method('getCountQueryHints')
            ->willReturn([]);

        $this->pager->expects($this->once())
            ->method('setQueryBuilder')
            ->with($qb);
        $this->pager->expects($this->once())
            ->method('setCountQb')
            ->with($countQb, []);
        $this->pager->expects($this->once())
            ->method('computeNbResult')
            ->willReturn(42);

        $this->assertDatasourceCalls($dataSource);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertEquals(42, $this->provider->getGridCount($gridName));
    }

    public function testGetCountWithoutCustomCountQuery()
    {
        $gridName = 'test';

        $this->assertConfigCalls();

        $qb = $this->createMock(QueryBuilder::class);

        $dataSource = $this->createMock(OrmDatasource::class);
        $dataSource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);
        $dataSource->expects($this->once())
            ->method('getCountQb')
            ->willReturn(null);
        $dataSource->expects($this->never())
            ->method('getCountQueryHints');

        $this->pager->expects($this->once())
            ->method('setQueryBuilder')
            ->with($qb);
        $this->pager->expects($this->never())
            ->method('setCountQb');
        $this->pager->expects($this->once())
            ->method('computeNbResult')
            ->willReturn(42);

        $this->assertDatasourceCalls($dataSource);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertEquals(42, $this->provider->getGridCount($gridName));
    }

    private function assertDatasourceCalls(\PHPUnit\Framework\MockObject\MockObject $dataSource)
    {
        $parameters = $this->createMock(ParameterBag::class);
        $parameters->expects($this->once())
            ->method('set')
            ->with(AbstractFilterExtension::FILTER_ROOT_PARAM, []);
        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);
        $grid->expects($this->once())
            ->method('getAcceptedDatasource')
            ->willReturn($dataSource);
        $this->gridManager->expects($this->once())
            ->method('getDatagridByRequestParams')
            ->willReturn($grid);
    }

    private function assertConfigCalls()
    {
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('getAclResource')
            ->willReturn(null);
        $this->gridManager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->willReturn($config);
    }
}
