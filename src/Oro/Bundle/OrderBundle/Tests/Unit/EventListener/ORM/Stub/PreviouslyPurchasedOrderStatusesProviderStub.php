<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub;

use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Provider\PreviouslyPurchasedOrderStatusesProvider;

class PreviouslyPurchasedOrderStatusesProviderStub extends PreviouslyPurchasedOrderStatusesProvider
    implements OrderStatusesProviderInterface
{
    public function getAvailableStatuses()
    {
        return [
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
            OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED
        ];
    }
}
