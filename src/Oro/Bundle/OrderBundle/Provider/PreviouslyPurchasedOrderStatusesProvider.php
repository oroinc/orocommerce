<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Service that provides available statuses for previously purchased orders
 */
class PreviouslyPurchasedOrderStatusesProvider implements OrderStatusesProviderInterface
{
    #[\Override]
    public function getAvailableStatuses(): array
    {
        return ExtendHelper::mapToEnumOptionIds(
            Order::INTERNAL_STATUS_CODE,
            [
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
            ]
        );
    }
}
