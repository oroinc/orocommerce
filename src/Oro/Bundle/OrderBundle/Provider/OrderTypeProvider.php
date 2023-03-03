<?php

namespace Oro\Bundle\OrderBundle\Provider;

/**
 * Provides available order types.
 */
class OrderTypeProvider
{
    public function getOrderTypeChoices(): array
    {
        return [
            'oro.order.order_type.primary_order' => 1,
            'oro.order.order_type.sub_order' => 2
        ];
    }
}
