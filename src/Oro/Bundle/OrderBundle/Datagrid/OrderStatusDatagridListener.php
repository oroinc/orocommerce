<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;

/**
 * Adds order status column to back-office order grid
 * when "Enable External Status Management" configuration option is enabled.
 */
class OrderStatusDatagridListener
{
    public function __construct(
        private OrderConfigurationProviderInterface $configurationProvider,
        private EnumOptionsProvider $enumOptionsProvider
    ) {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            $config = $event->getConfig();
            $config->addColumn(
                'status',
                [
                    'label' => 'oro.order.status.label',
                    'frontend_type' => 'select',
                    'data_name' => 'status',
                    'choices' => $this->enumOptionsProvider->getEnumChoicesByCode('order_status'),
                    'translatable_options' => false,
                ]
            );
            $config->addFilter(
                'status',
                ['type' => 'enum', 'data_name' => 'status', 'enum_code' => 'order_status']
            );
            $config->addSorter('status', ['data_name' => 'status']);
            $config->moveColumnBefore('status', 'internal_status');
            $config->moveFilterBefore('status', 'internal_status');
        }
    }
}
