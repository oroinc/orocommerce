<?php

namespace Oro\Bundle\OrderBundle\Provider;

class OrderStatusesProvider
{
    const INTERNAL_STATUS_OPEN = 'open';
    const INTERNAL_STATUS_CANCELLED = 'cancelled';
    const INTERNAL_STATUS_CLOSED = 'closed';
    const INTERNAL_STATUS_ARCHIVED = 'archived';
    const INTERNAL_STATUS_SHIPPED = 'shipped';

    /**
     * Statuses available for previously purchased feature
     * @var array
     */
    protected $previouslyPurchasedOrderStatuses = [
        self::INTERNAL_STATUS_OPEN,
        self::INTERNAL_STATUS_CLOSED,
        self::INTERNAL_STATUS_ARCHIVED,
        self::INTERNAL_STATUS_SHIPPED,
    ];

    /**
     * @return array
     */
    public function getOrderStatusesForPreviouslyPurchased()
    {
        return $this->previouslyPurchasedOrderStatuses;
    }
}
