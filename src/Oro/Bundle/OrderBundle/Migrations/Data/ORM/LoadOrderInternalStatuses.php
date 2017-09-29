<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;

class LoadOrderInternalStatuses extends AbstractEnumFixture
{
    /** @var array */
    protected static $data = [
        OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN => 'Open',
        OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED => 'Cancelled',
        OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED => 'Shipped',
        OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED => 'Closed',
        OrderStatusesProviderInterface::INTERNAL_STATUS_ARCHIVED => 'Archived',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return self::$data;
    }

    /**
     * Returns array of data keys
     *
     * @return array
     */
    public static function getDataKeys()
    {
        return array_keys(self::$data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return Order::INTERNAL_STATUS_CODE;
    }
}
