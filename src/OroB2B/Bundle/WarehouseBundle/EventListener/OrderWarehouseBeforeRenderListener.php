<?php

namespace OroB2B\Bundle\WarehouseBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;

class OrderWarehouseBeforeRenderListener
{
    /**
     * @var WarehouseCounter
     */
    protected $warehouseCounter;

    /**
     * OrderWarehouseBeforeRenderListener constructor.
     *
     * @param WarehouseCounter $warehouseCounter
     */
    public function __construct(WarehouseCounter $warehouseCounter)
    {
        $this->warehouseCounter = $warehouseCounter;
    }

    /**
     * @param ValueRenderEvent $event
     */
    public function onWarehouseOrderDisplay(ValueRenderEvent $event)
    {
        if ($event->getFieldConfigId()->getFieldName() != 'warehouse'
            || $event->getFieldConfigId()->getClassName() != Order::class
        ) {
            return;
        }

        if (!$this->warehouseCounter->areMoreWarehouses()) {
            $event->setFieldVisibility(false);

            return;
        }

        $event->setFieldViewValue($event->getFieldValue());
    }
}
