<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as TestCustomerUserData;

class LoadSubOrders extends LoadOrders
{
    public const SUB_ORDER_1_OF_ORDER_1 = 'sub_order_1_of_simple_order';

    /**
     * @var array
     */
    protected $orders = [
        self::SUB_ORDER_1_OF_ORDER_1 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => TestCustomerUserData::AUTH_USER,
            'poNumber' => 'PO_SUB1',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'parentOrder' => self::ORDER_1,
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
