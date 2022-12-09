<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * If {@link Configuration::SHOW_SUBORDERS_IN_ORDER_HISTORY} disabled:
 * - hide orderType column for frontend-orders-grid.
 * - hide sub-orders in frontend-orders-grid.
 * If {@link Configuration::SHOW_MAIN_ORDERS_IN_ORDER_HISTORY} disabled:
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

            $query = $config->getOrmQuery();
            $query->addSelect(
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
        $dataGrid = $event->getDatagrid();

        /** @var QueryBuilder $qb */
        $qb = $dataGrid->getDatasource()->getQueryBuilder();

        // Hide subOrders if show suborders config is disabled.
        if (!$this->multiShippingConfigProvider->isShowSubordersInOrderHistoryEnabled()) {
            $qb->andWhere($qb->expr()->isNull('order1.parent'));
        }

        // Hide main orders if config is disabled.
        if ($this->multiShippingConfigProvider->isShowMainOrderInOrderHistoryDisabled()) {
            /** @var QueryBuilder $subQuery */
            $subQuery = $this->doctrine->getManagerForClass(Order::class)
                ->createQueryBuilder()
                ->select('IDENTITY(osub.parent)')
                ->from(Order::class, 'osub')
                ->where('IDENTITY(osub.parent) is not null');

            $qb->andWhere($qb->expr()->notIn('order1.id', $subQuery->getDQL()));
        }
    }

    private function addOrderTypeColumnAndFilter(DatagridConfiguration $config)
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
