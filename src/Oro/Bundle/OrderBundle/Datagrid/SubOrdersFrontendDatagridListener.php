<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * When showing suborders in order history is disabled:
 * - hide orderType column for frontend-orders-grid.
 * - hide sub-orders in frontend-orders-grid.
 * When showing main orders in order history is disabled:
 * - hide orderType column for frontend-orders-grid.
 * - hide main orders in frontend-orders-grid.
 */
class SubOrdersFrontendDatagridListener
{
    private const ORDER_TYPE = 'orderType';

    private ConfigProvider $multiShippingConfigProvider;
    private ManagerRegistry $doctrine;

    public function __construct(ConfigProvider $multiShippingConfigProvider, ManagerRegistry $doctrine)
    {
        $this->multiShippingConfigProvider = $multiShippingConfigProvider;
        $this->doctrine = $doctrine;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        // Column and filter should be displayed only when subOrders and Main Orders are allowed in the grid.
        if ($this->multiShippingConfigProvider->isShowMainOrdersAndSubOrdersInOrderHistoryEnabled()) {
            $config = $event->getConfig();

            $this->addOrderTypeColumnAndFilter($config);

            $config->getOrmQuery()->addSelect(
                "CASE WHEN IDENTITY(order1.parent) IS NULL THEN 'oro.order.order_type.primary_order' "
                . "ELSE 'oro.order.order_type.sub_order' END AS orderType"
            );
        }
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        $this->hideMainOrders($event);
    }

    private function hideMainOrders(BuildAfter $event): void
    {
        /** @var QueryBuilder $qb */
        $qb = $event->getDatagrid()->getDatasource()->getQueryBuilder();

        // Hide subOrders if show suborders config is disabled.
        if (!$this->multiShippingConfigProvider->isShowSubordersInOrderHistoryEnabled()) {
            $qb->andWhere('order1.parent IS NULL');
        }

        // Hide main orders if config is disabled.
        if ($this->multiShippingConfigProvider->isShowMainOrderInOrderHistoryDisabled()) {
            /** @var EntityManagerInterface $em */
            $em = $this->doctrine->getManagerForClass(Order::class);
            $subQuery = $em->createQueryBuilder()
                ->select('IDENTITY(osub.parent)')
                ->from(Order::class, 'osub')
                ->where('IDENTITY(osub.parent) IS NOT NULL');

            $qb->andWhere('order1.id NOT IN(' . $subQuery->getDQL() . ')');
        }
    }

    private function addOrderTypeColumnAndFilter(DatagridConfiguration $config): void
    {
        $config->addColumn(self::ORDER_TYPE, [
            'label' => 'oro.order.order_type.label',
            'type' => 'twig',
            'frontend_type' => 'html',
            'template' => '@OroOrder/Order/Datagrid/orderType.html.twig',
            'renderable' => false
        ]);

        $config->addSorter(self::ORDER_TYPE, [
            'data_name' => 'orderType'
        ]);

        $config->addFilter(self::ORDER_TYPE, [
            'type' => 'single_choice',
            'data_name' => 'orderType',
            'enabled' => false,
            'options' => [
                'field_options' => [
                    'choices' => [
                        'oro.order.order_type.primary_order' => 'oro.order.order_type.primary_order',
                        'oro.order.order_type.sub_order' => 'oro.order.order_type.sub_order'
                    ]
                ]
            ]
        ]);
    }
}
