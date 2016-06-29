<?php

namespace OroB2B\Bundle\WarehouseBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;

class OrderWarehouseGridListener
{
    const WAREHOUSE_COLUMN_NAME = 'warehouse';
    const WAREHOUSE_COLUMN_LABEL = 'orob2b.warehouse.datagrid.order.label';

    /**
     * @var WarehouseCounter
     */
    protected $warehouseCounter;

    /**
     * @param WarehouseCounter $warehouseCounter
     * @internal param DoctrineHelper $doctrineHelper
     */
    public function __construct(WarehouseCounter $warehouseCounter)
    {
        $this->warehouseCounter = $warehouseCounter;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        if (!$this->warehouseCounter->areMoreWarehouses()) {
            return;
        }

        $config = $event->getConfig();
        $from = $config->offsetGetByPath('[source][query][from]');
        $rootEntityAlias = $from[0]['alias'];

        // add select for warehouse
        $select = $config->offsetGetByPath('[source][query][select]');
        $select[] = 'wh.name as ' . self::WAREHOUSE_COLUMN_NAME;
        $config->offsetSetByPath('[source][query][select]', $select);

        // add left join config of warehouse
        $leftJoins = $config->offsetGetByPath('[source][query][join][left]', []);
        $leftJoins[] = [
            'join' => $rootEntityAlias . '.warehouse',
            'alias' => 'wh'
        ];
        $config->offsetSetByPath('[source][query][join][left]', $leftJoins);

        // add column to grid and hide it by default
        $config->offsetSetByPath(
            sprintf('[columns][%s]', self::WAREHOUSE_COLUMN_NAME),
            [
                'label' => self::WAREHOUSE_COLUMN_LABEL,
                'renderable' => false
            ]
        );
    }
}
