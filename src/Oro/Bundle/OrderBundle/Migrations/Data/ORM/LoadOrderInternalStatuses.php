<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\OrderBundle\Entity\Order;

class LoadOrderInternalStatuses extends AbstractEnumFixture
{
    /** @var array */
    protected static $data = [
        Order::INTERNAL_STATUS_OPEN => 'Open',
        Order::INTERNAL_STATUS_CANCELLED => 'Cancelled',
        Order::INTERNAL_STATUS_SHIPPED => 'Shipped',
        Order::INTERNAL_STATUS_CLOSED => 'Closed',
        Order::INTERNAL_STATUS_ARCHIVED => 'Archived',
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
