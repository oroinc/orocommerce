<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\OrdersCreatedAt;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as TestCustomerUserData;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

/**
 * Orders created in date range 2022-12-05 - 2023-01-04
 */
class LoadOrdersCreatedAtRangeDuration31Days extends LoadOrdersCreatedAtRangeDuration4Days
{
    protected const ORDER_7 = 'simple_order7';

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
            'createdAt' => '2022-12-05 10:00:00',
        ],
        self::ORDER_1 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => '1234567890',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-01 10:00:00',
        ],
        self::ORDER_2 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO2',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-01 13:00:00',
        ],
        self::ORDER_3 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO3',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-02 10:00:00',
        ],
        self::ORDER_4 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO3',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-02 14:00:00',
        ],
        self::ORDER_5 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO3',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-03 10:00:00',
        ],
        self::ORDER_6 => [
            'user' => LoadOrderUsers::ORDER_USER_1,
            'customerUser' => self::ACCOUNT_USER,
            'poNumber' => 'PO6',
            'customerNotes' => 'Test customer user notes',
            'currency' => 'USD',
            'subtotal' => self::SUBTOTAL,
            'total' => self::TOTAL,
            'paymentTerm' => LoadPaymentTermData::PAYMENT_TERM_NET_10,
            'createdAt' => '2023-01-04 10:00:00',
        ],
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
            'createdAt' => '2023-01-04 10:00:00',
        ],
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
            'createdAt' => '2023-01-04 10:00:00',
        ],
    ];
}
