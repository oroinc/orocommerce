<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;

/**
 * Adds order status column to back-office order grid
 * when "Enable External Status Management" configuration option is enabled.
 */
class OrderStatusDatagridListener
{
    private OrderConfigurationProviderInterface $configurationProvider;

    public function __construct(OrderConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            $config = $event->getConfig();
            $query = $config->getOrmQuery();
            $query->addSelect('status.name as statusName');
            $query->addSelect('status.id as statusId');
            $query->addLeftJoin('order1.status', 'status');
            $config->addColumn('statusName', ['label' => 'oro.order.status.label']);
            $config->addFilter(
                'statusName',
                ['type' => 'enum', 'data_name' => 'statusId', 'enum_code' => 'order_status']
            );
            $config->addSorter('statusName', ['data_name' => 'statusName']);
            $config->moveColumnBefore('statusName', 'internalStatusName');
            $config->moveFilterBefore('statusName', 'internalStatusName');
        }
    }
}
