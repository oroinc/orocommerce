<?php

namespace Oro\Bundle\OrderBundle\Provider;

interface OrderStatusesProviderInterface
{
    const INTERNAL_STATUS_OPEN = 'open';
    const INTERNAL_STATUS_CANCELLED = 'cancelled';
    const INTERNAL_STATUS_CLOSED = 'closed';
    const INTERNAL_STATUS_ARCHIVED = 'archived';
    const INTERNAL_STATUS_SHIPPED = 'shipped';

    /**
     * @return array
     */
    public function getAvailableStatuses();
}
