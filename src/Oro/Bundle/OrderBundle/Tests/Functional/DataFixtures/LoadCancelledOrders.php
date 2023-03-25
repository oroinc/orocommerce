<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as TestCustomerUserData;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;

class LoadCancelledOrders extends LoadOrders
{
    public const CANCELLED_ORDER_1 = 'cancelled_order_1';

    /**
     * @var array
     */
    protected $orders = [
        self::CANCELLED_ORDER_1 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => TestCustomerUserData::AUTH_USER,
            'poNumber' => 'PO_SUB1',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'internalStatus' => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrders::class,
        ];
    }
}
