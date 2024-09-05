<?php

namespace Oro\Bundle\OrderBundle\Provider;

/**
 * Represents a service that provides available internal order statuses.
 */
interface OrderStatusesProviderInterface
{
    public const INTERNAL_STATUS_OPEN = 'open';
    public const INTERNAL_STATUS_CANCELLED = 'cancelled';
    public const INTERNAL_STATUS_CLOSED = 'closed';

    /**
     * @return string[]
     */
    public function getAvailableStatuses(): array;
}
