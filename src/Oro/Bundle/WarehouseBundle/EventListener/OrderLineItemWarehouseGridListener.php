<?php

namespace Oro\Bundle\WarehouseBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;

class OrderLineItemWarehouseGridListener
{
    const WAREHOUSE_COLUMN_NAME = 'warehouse';
    const WAREHOUSE_COLUMN_LABEL = 'oro.warehouse.datagrid.order_line_item.label';

    /**
     * @var WarehouseCounter
     */
    protected $warehouseCounter;

    /**
     * @param WarehouseCounter $warehouseCounter
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

        // add column to grid and hide it by default
        $config->offsetSetByPath(
            sprintf('[columns][%s]', self::WAREHOUSE_COLUMN_NAME),
            [
                'label' => self::WAREHOUSE_COLUMN_LABEL,
            ]
        );
    }
}
