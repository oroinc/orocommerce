<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;

/**
 * Adds order status column to storefront order grid
 * when "Enable External Status Management" configuration option is enabled
 * or adds order internal status column to storefront order grid when this option is disabled.
 */
class OrderStatusFrontendDatagridListener
{
    private OrderConfigurationProviderInterface $configurationProvider;
    private EnumValueProvider $enumValueProvider;

    public function __construct(
        OrderConfigurationProviderInterface $configurationProvider,
        EnumValueProvider $enumValueProvider
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->enumValueProvider = $enumValueProvider;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $fieldName = 'internal_status';
        $enumCode = 'order_internal_status';
        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            $fieldName = 'status';
            $enumCode = 'order_status';
        }

        $config = $event->getConfig();
        $query = $config->getOrmQuery();
        $query->addSelect('status.name as statusName');
        $query->addSelect('status.id as statusId');
        $query->addLeftJoin('order1.' . $fieldName, 'status');
        $query->addHint('HINT_TRANSLATABLE');
        $config->addColumn('statusName', ['label' => 'oro.frontend.order.order_status.label']);
        $config->addFilter('statusName', [
            'type'      => 'choice',
            'data_name' => 'statusId',
            'options'   => [
                'field_options' => [
                    'choices'              => $this->enumValueProvider->getEnumChoicesByCode($enumCode),
                    'translatable_options' => false,
                    'multiple'             => true
                ]
            ]
        ]);
        $config->addSorter('statusName', ['data_name' => 'statusName']);
        $config->moveColumnAfter('statusName', 'total');
        $config->moveFilterAfter('statusName', 'total');
    }
}
