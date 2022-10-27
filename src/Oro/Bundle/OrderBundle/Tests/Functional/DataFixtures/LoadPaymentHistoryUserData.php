<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\AbstractLoadMultipleUserData;

class LoadPaymentHistoryUserData extends AbstractLoadMultipleUserData
{
    const USER_PAYMENT_HISTORY_VIEWER = 'order-payment-history-viewer';
    const USER_ORDER_VIEWER = 'order-viewer';

    const ROLE_VIEW_HISTORY = 'ORDER_PAYMENT_HISTORY_VIEW';
    const ROLE_VIEW_ORDER = 'ORDER_VIEW';

    /**
     * {@inheritdoc}
     */
    protected function getRolesData()
    {
        return [
            self::ROLE_VIEW_HISTORY => [
                [
                    'class' => Order::class,
                    'acls'  => [
                        [
                            AbstractLoadMultipleUserData::ACL_PERMISSION => 'VIEW',
                            AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                        ],
                        [
                            AbstractLoadMultipleUserData::ACL_PERMISSION => 'VIEW_PAYMENT_HISTORY',
                            AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                        ],
                    ],
                ],
            ],
            self::ROLE_VIEW_ORDER => [
                [
                    'class' => Order::class,
                    'acls'  => [
                        [
                            AbstractLoadMultipleUserData::ACL_PERMISSION => 'VIEW',
                            AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUsersData()
    {
        return [
            [
                'email'     => self::USER_PAYMENT_HISTORY_VIEWER . '@test.com',
                'username'  => self::USER_PAYMENT_HISTORY_VIEWER,
                'password'  => self::USER_PAYMENT_HISTORY_VIEWER,
                'firstname' => 'User',
                'lastname'  => 'User',
                'userRoles'     => [self::ROLE_VIEW_HISTORY],
            ],
            [
                'email'     => self::USER_ORDER_VIEWER . '@test.com',
                'username'  => self::USER_ORDER_VIEWER,
                'password'  => self::USER_ORDER_VIEWER,
                'firstname' => 'User',
                'lastname'  => 'User',
                'userRoles'     => [self::ROLE_VIEW_ORDER],
            ],
        ];
    }
}
