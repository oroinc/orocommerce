<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Datagrid\SubOrdersFrontendDatagridListener;
use Oro\Bundle\OrderBundle\Entity\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubOrdersFrontendDatagridListenerTest extends TestCase
{
    private ConfigProvider|MockObject $multiShippingConfigProvider;
    private ManagerRegistry|MockObject $doctrine;
    private SubOrdersFrontendDatagridListener $listener;

    protected function setUp(): void
    {
        $this->multiShippingConfigProvider = $this->createMock(ConfigProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->listener = new SubOrdersFrontendDatagridListener(
            $this->multiShippingConfigProvider,
            $this->doctrine
        );
    }

    public function testOnBuildBefore()
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowMainOrdersAndSubOrdersInOrderHistoryEnabled')
            ->willReturn(true);

        $config = DatagridConfiguration::create([]);

        $event = $this->createMock(BuildBefore::class);
        $event->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->listener->onBuildBefore($event);

        $columns = $config->offsetGetByPath('[columns]');
        $sorters = $config->offsetGetByPath('[sorters][columns]');
        $filters = $config->offsetGetByPath('[filters][columns]');

        $this->assertNotEmpty($columns);
        $this->assertNotEmpty($sorters);
        $this->assertNotEmpty($filters);

        $this->assertArrayHasKey('orderType', $columns);
        $this->assertArrayHasKey('orderType', $sorters);
        $this->assertArrayHasKey('orderType', $filters);

        $select = $config->getOrmQuery()->getSelect();
        $this->assertNotEmpty($select);
        $this->assertContains(
            "CASE WHEN IDENTITY(order1.parent) IS NULL THEN 'oro.order.order_type.primary_order' "
            . "ELSE 'oro.order.order_type.sub_order' END AS orderType",
            $select
        );
    }

    public function testOnBuildBeforeWhenMultiShippingDisabled()
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowMainOrdersAndSubOrdersInOrderHistoryEnabled')
            ->willReturn(false);

        $event = $this->createMock(BuildBefore::class);
        $event->expects($this->never())
            ->method('getConfig');

        $this->listener->onBuildBefore($event);
    }

    public function testOnBuildAfterWhenShowMainOrdersDisabled()
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(true);

        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowMainOrderInOrderHistoryDisabled')
            ->willReturn(true);

        $datagrid = $this->createMock(DatagridInterface::class);

        $event = $this->createMock(BuildAfter::class);
        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $datasource = $this->createMock(OrmDatasource::class);

        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $em = $this->createMock(EntityManager::class);
        $qb = new QueryBuilder($em);

        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($em);

        $subQb = new QueryBuilder($em);
        $expr = new Expr();

        $em->expects($this->once())
            ->method('getExpressionBuilder')
            ->willReturn($expr);

        $em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($subQb);

        $this->listener->onBuildAfter($event);

        $this->assertEquals(
            'SELECT WHERE order1.id NOT IN(SELECT IDENTITY(osub.parent) FROM Oro\Bundle\OrderBundle\Entity\Order osub '
            . 'WHERE IDENTITY(osub.parent) is not null)',
            $qb->getDQL()
        );
    }

    public function testOnBuildAfterWhenShowMainOrdersEnabled()
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(true);

        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowMainOrderInOrderHistoryDisabled')
            ->willReturn(false);

        $datagrid = $this->createMock(DatagridInterface::class);

        $event = $this->createMock(BuildAfter::class);
        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $datasource = $this->createMock(OrmDatasource::class);

        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $em = $this->createMock(EntityManager::class);
        $qb = new QueryBuilder($em);

        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->listener->onBuildAfter($event);

        $this->assertEmpty($qb->getDQLPart('select'));
        $this->assertNull($qb->getDQLPart('where'));
    }

    public function testOnBuildAfterShowSubordersInOrderHistoryDisabled()
    {
        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(false);

        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isShowMainOrderInOrderHistoryDisabled')
            ->willReturn(false);

        $datagrid = $this->createMock(DatagridInterface::class);

        $event = $this->createMock(BuildAfter::class);
        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $datasource = $this->createMock(OrmDatasource::class);

        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $em = $this->createMock(EntityManager::class);
        $qb = new QueryBuilder($em);

        $expr = new Expr();

        $em->expects($this->once())
            ->method('getExpressionBuilder')
            ->willReturn($expr);

        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->listener->onBuildAfter($event);

        $this->assertNotNull($qb->getDQLPart('where'));
        $this->assertEquals('order1.parent IS NULL', $qb->getDQLPart('where'));
    }
}
