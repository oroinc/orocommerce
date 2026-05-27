<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Event;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderDuplicator;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after an order is duplicated in {@link OrderDuplicator}.
 */
class OrderDuplicateAfterEvent extends Event
{
    public function __construct(
        private readonly Order $order,
        private readonly Order $duplicatedOrder
    ) {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getDuplicatedOrder(): Order
    {
        return $this->duplicatedOrder;
    }
}
