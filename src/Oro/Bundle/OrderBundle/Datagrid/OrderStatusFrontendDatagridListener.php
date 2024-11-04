<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;

/**
 * Adds order status column to storefront order grid
 * when "Enable External Status Management" configuration option is enabled
 * or adds order internal status column to storefront order grid when this option is disabled.
 */
class OrderStatusFrontendDatagridListener
{
    public function __construct(
        private OrderConfigurationProviderInterface $configurationProvider,
        private EnumOptionsProvider $enumOptionsProvider
    ) {
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
        $query->addLeftJoin(
            EnumOption::class,
            'status',
            Join::WITH,
            "JSON_EXTRACT(order1.serialized_data, '$fieldName') = status"
        );
        $query->addHint('HINT_TRANSLATABLE');
        $config->addColumn('statusName', ['label' => 'oro.frontend.order.order_status.label']);
        $config->addFilter('statusName', [
            'type'      => 'choice',
            'data_name' => 'statusId',
            'options'   => [
                'field_options' => [
                    'choices'              => $this->enumOptionsProvider->getEnumChoicesByCode($enumCode),
                    'translatable_options' => false,
                    'multiple'             => true
                ]
            ]
        ]);
        $config->addSorter('statusName', ['data_name' => 'statusName']);
        $config->moveColumnBefore('statusName', 'total');
        $config->moveFilterBefore('statusName', 'total');
    }
}
