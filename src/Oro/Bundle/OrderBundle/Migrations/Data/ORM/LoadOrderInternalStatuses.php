<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;

/**
 * Loads supported internal order statuses.
 */
class LoadOrderInternalStatuses extends AbstractEnumFixture
{
    private static $data = [
        OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN => 'Open',
        OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED => 'Cancelled',
        OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED => 'Closed'
    ];

    public static function getDataKeys(): array
    {
        return array_keys(self::$data);
    }

    #[\Override]
    protected function getData(): array
    {
        return self::$data;
    }

    #[\Override]
    protected function getEnumCode(): string
    {
        return Order::INTERNAL_STATUS_CODE;
    }
}
