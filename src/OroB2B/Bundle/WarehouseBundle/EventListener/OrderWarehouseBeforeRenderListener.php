<?php

namespace OroB2B\Bundle\WarehouseBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class OrderWarehouseBeforeRenderListener
{
    /**
     * @param ValueRenderEvent $event
     */
    public function onWarehouseOrderDisplay(ValueRenderEvent $event)
    {
        if (!$event->getFieldValue() instanceof Warehouse
            || $event->getFieldConfigId()->getClassName() != Order::class
        ) {
            return;
        }

        $event->setFieldViewValue($event->getFieldValue());
    }
}
