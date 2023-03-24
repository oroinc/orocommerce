<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\OrdersCreatedAt;

use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

/**
 * Orders created in date range 2023-01-04 - 2023-01-04
 */
class LoadOrdersCreatedAtRangeDuration1Day extends LoadOrdersCreatedAtRangeDuration4Days
{
    public const ORDER_7 = 'simple_order7';

    /** @var array */
    protected $orders = [
        self::ORDER_7 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO3',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-04 10:00:00',
        ],
    ];
}
