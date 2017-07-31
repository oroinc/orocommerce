<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\OrderBundle\Entity\Order;

class LoadOrderInternalStatuses extends AbstractEnumFixture
{
    /** @var array */
    protected static $data = [
        'open' => 'Open',
        'cancelled' => 'Cancelled',
        'shipped' => 'Shipped',
        'closed' => 'Closed',
        'archived' => 'Archived',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return self::$data;
    }

    /**
     * Returns array of data keys.
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
