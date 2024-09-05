<?php

namespace Oro\Bundle\OrderBundle\Provider;

/**
 * Provides supported internal order statuses for previously purchased feature.
 */
class PreviouslyPurchasedOrderStatusesProvider implements OrderStatusesProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAvailableStatuses(): array
    {
        return [
            self::INTERNAL_STATUS_OPEN,
            self::INTERNAL_STATUS_CLOSED
        ];
    }
}
