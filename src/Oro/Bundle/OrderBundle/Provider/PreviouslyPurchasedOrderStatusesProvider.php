<?php

namespace Oro\Bundle\OrderBundle\Provider;

class PreviouslyPurchasedOrderStatusesProvider implements OrderStatusesProviderInterface
{
    /**
     * Statuses available for previously purchased feature
     * @var array
     */
    protected $previouslyPurchasedOrderStatuses = [
        OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
        OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
        OrderStatusesProviderInterface::INTERNAL_STATUS_ARCHIVED,
        OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
    ];

    /**
     * @return array
     */
    public function getAvailableStatuses()
    {
        return $this->previouslyPurchasedOrderStatuses;
    }
}
