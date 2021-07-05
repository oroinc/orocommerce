<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\AbstractLoadMultipleUserData;

class LoadChargeAuthorizedPaymentsPermissionUserData extends AbstractLoadMultipleUserData
{
    const USER_WITH_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION = 'order-with-charge-authorized-payments-permission';
    const USER_PAYMENT_HISTORY_VIEWER = 'order-payment-history-viewer';

    const VIEW_PAYMENT_HISTORY_PERMISSION_NAME = 'VIEW_PAYMENT_HISTORY';
    const CHARGE_AUTHORIZED_PAYMENTS_PERMISSION_NAME = 'CHARGE_AUTHORIZED_PAYMENTS';

    const ROLE_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION = 'ROLE_CHARGE_PAYMENTS';
    const ROLE_VIEW_HISTORY = 'ROLE_ORDER_VIEW';

    /**
     * {@inheritdoc}
     */
    protected function getRolesData()
    {
        return [
            self::ROLE_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION => [
                [
                    'class' => Order::class,
                    'acls' => [
                        [
                            AbstractLoadMultipleUserData::ACL_PERMISSION => 'VIEW',
                            AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                        ],
                        [
                            AbstractLoadMultipleUserData::ACL_PERMISSION => self::VIEW_PAYMENT_HISTORY_PERMISSION_NAME,
                            AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                        ],
                        [
                            AbstractLoadMultipleUserData::ACL_PERMISSION
                                => self::CHARGE_AUTHORIZED_PAYMENTS_PERMISSION_NAME,
                            AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                        ],
                    ],
                ],
            ],
            self::ROLE_VIEW_HISTORY => [
                [
                    'class' => Order::class,
                    'acls' => [
                        [
                            AbstractLoadMultipleUserData::ACL_PERMISSION => 'VIEW',
                            AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                        ],
                        [
                            AbstractLoadMultipleUserData::ACL_PERMISSION => self::VIEW_PAYMENT_HISTORY_PERMISSION_NAME,
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
                'email' => self::USER_WITH_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION.'@test.com',
                'username' => self::USER_WITH_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION,
                'password' => self::USER_WITH_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION,
                'firstname' => 'User',
                'lastname' => 'User',
                'userRoles' => [self::ROLE_CHARGE_AUTHORIZED_PAYMENTS_PERMISSION],
            ],
            [
                'email' => self::USER_PAYMENT_HISTORY_VIEWER.'@test.com',
                'username' => self::USER_PAYMENT_HISTORY_VIEWER,
                'password' => self::USER_PAYMENT_HISTORY_VIEWER,
                'firstname' => 'User',
                'lastname' => 'User',
                'userRoles' => [self::ROLE_VIEW_HISTORY],
            ],
        ];
    }
}
