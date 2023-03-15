<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
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
use Oro\Bundle\OrderBundle\Provider\OrderTypeProvider;

class SubOrdersFrontendDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingConfigProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SubOrdersFrontendDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->multiShippingConfigProvider = $this->createMock(ConfigProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $orderTypeProvider = $this->createMock(OrderTypeProvider::class);
        $orderTypeProvider->expects(self::any())
            ->method('getOrderTypeChoices')
            ->willReturn(['primary_order' => 1, 'sub_order' => 2]);

        $this->listener = new SubOrdersFrontendDatagridListener(
            $this->multiShippingConfigProvider,
            $this->doctrine,
            $orderTypeProvider
        );
    }

    public function testOnBuildBefore()
    {
        $config = DatagridConfiguration::create([]);

        $this->multiShippingConfigProvider->expects(self::once())
            ->method('isShowMainOrdersAndSubOrdersInOrderHistoryEnabled')
            ->willReturn(true);

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);
        $this->listener->onBuildBefore($event);

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'select' => [
                            'CASE WHEN IDENTITY(order1.parent) IS NULL THEN 1 ELSE 2 END AS orderType'
                        ]
                    ]
                ],
                'columns' => [
                    'orderType' => [
                        'label' => 'oro.order.order_type.label',
                        'frontend_type' => 'select',
                        'choices' => ['primary_order' => 1, 'sub_order' => 2],
                        'renderable' => false
                    ]
                ],
                'filters' => [
                    'columns' => [
                        'orderType' => [
                            'type' => 'single_choice',
                            'data_name' => 'orderType',
                            'enabled' => false,
                            'options' => [
                                'field_options' => [
                                    'choices' => ['primary_order' => 1, 'sub_order' => 2]
                                ]
                            ]
                        ]
                    ]
                ],
                'sorters' => [
                    'columns' => [
                        'orderType' => [
                            'data_name' => 'orderType'
                        ]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeWhenMultiShippingDisabled()
    {
        $config = DatagridConfiguration::create([]);

        $this->multiShippingConfigProvider->expects(self::once())
            ->method('isShowMainOrdersAndSubOrdersInOrderHistoryEnabled')
            ->willReturn(false);

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $config);
        $this->listener->onBuildBefore($event);

        self::assertEquals([], $config->toArray());
    }

    public function testOnBuildAfterWhenShowMainOrdersDisabled()
    {
        $this->multiShippingConfigProvider->expects(self::once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(true);

        $this->multiShippingConfigProvider->expects(self::once())
            ->method('isShowMainOrderInOrderHistoryDisabled')
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($em);

        $qb = new QueryBuilder($em);
        $subQb = new QueryBuilder($em);

        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($subQb);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->listener->onBuildAfter(new BuildAfter($datagrid));

        self::assertEquals(
            'SELECT WHERE order1.id NOT IN(SELECT IDENTITY(osub.parent)'
            . ' FROM Oro\Bundle\OrderBundle\Entity\Order osub '
            . 'WHERE IDENTITY(osub.parent) IS NOT NULL)',
            $qb->getDQL()
        );
    }

    public function testOnBuildAfterWhenShowMainOrdersEnabled()
    {
        $this->multiShippingConfigProvider->expects(self::once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(true);

        $this->multiShippingConfigProvider->expects(self::once())
            ->method('isShowMainOrderInOrderHistoryDisabled')
            ->willReturn(false);

        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->listener->onBuildAfter(new BuildAfter($datagrid));

        self::assertEmpty($qb->getDQLPart('select'));
        self::assertNull($qb->getDQLPart('where'));
    }

    public function testOnBuildAfterShowSubordersInOrderHistoryDisabled()
    {
        $this->multiShippingConfigProvider->expects(self::once())
            ->method('isShowSubordersInOrderHistoryEnabled')
            ->willReturn(false);

        $this->multiShippingConfigProvider->expects(self::once())
            ->method('isShowMainOrderInOrderHistoryDisabled')
            ->willReturn(false);

        $qb = new QueryBuilder($this->createMock(EntityManagerInterface::class));

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->listener->onBuildAfter(new BuildAfter($datagrid));

        self::assertNotNull($qb->getDQLPart('where'));
        self::assertEquals('order1.parent IS NULL', $qb->getDQLPart('where'));
    }
}
