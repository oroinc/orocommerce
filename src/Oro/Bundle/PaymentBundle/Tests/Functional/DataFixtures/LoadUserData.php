<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\AbstractLoadMultipleUserData;

class LoadUserData extends AbstractLoadMultipleUserData
{
    const USER_VIEWER = 'payment-user-viewer';
    const USER_EDITOR = 'payment-user-editor';
    const USER_VIEWER_CREATOR = 'payment-user-viewer-creator';

    const ROLE_VIEW = 'PAYMENT_ROLE_VIEW';
    const ROLE_EDIT = 'PAYMENT_ROLE_EDIT';
    const ROLE_CREATE = 'PAYMENT_ROLE_CREATE';

    /**
     * @var array
     */
    protected $roles = [
        self::ROLE_VIEW => [
            [
                'class' => PaymentMethodsConfigsRule::class,
                'acls'  => [
                    [
                        AbstractLoadMultipleUserData::ACL_PERMISSION => 'VIEW',
                        AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                    ],
                ],
            ],
            [
                'class' => Channel::class,
                'acls'  => [
                    [
                        AbstractLoadMultipleUserData::ACL_PERMISSION => 'VIEW',
                        AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                    ],
                ],
            ],
        ],
        self::ROLE_EDIT => [
            [
                'class' => PaymentMethodsConfigsRule::class,
                'acls'  => [
                    [
                        AbstractLoadMultipleUserData::ACL_PERMISSION => 'EDIT',
                        AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                    ],
                ],
            ],
            [
                'class' => Channel::class,
                'acls'  => [
                    [
                        AbstractLoadMultipleUserData::ACL_PERMISSION => 'EDIT',
                        AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                    ],
                ],
            ],
        ],
        self::ROLE_CREATE => [
            [
                'class' => PaymentMethodsConfigsRule::class,
                'acls'  => [
                    [
                        AbstractLoadMultipleUserData::ACL_PERMISSION => 'CREATE',
                        AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                    ],
                ],
            ],
            [
                'class' => Channel::class,
                'acls'  => [
                    [
                        AbstractLoadMultipleUserData::ACL_PERMISSION => 'CREATE',
                        AbstractLoadMultipleUserData::ACL_LEVEL => 'SYSTEM',
                    ],
                ],
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $users = [
        [
            'email'     => 'payment-user-viewer@example.com',
            'username'  => self::USER_VIEWER,
            'password'  => self::USER_VIEWER,
            'firstname' => 'PaymentUser1FN',
            'lastname'  => 'PaymentUser1LN',
            'userRoles'     => [self::ROLE_VIEW],
        ],
        [
            'email'     => 'payment-user-editor@example.com',
            'username'  => self::USER_EDITOR,
            'password'  => self::USER_EDITOR,
            'firstname' => 'PaymentUser2FN',
            'lastname'  => 'PaymentUser2LN',
            'userRoles'     => [self::ROLE_EDIT],
        ],
        [
            'email'     => 'payment-user-viewer-creator@example.com',
            'username'  => self::USER_VIEWER_CREATOR,
            'password'  => self::USER_VIEWER_CREATOR,
            'firstname' => 'PaymentUser2FN',
            'lastname'  => 'PaymentUser2LN',
            'userRoles'     => [self::ROLE_VIEW, self::ROLE_CREATE],
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected function getRolesData()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUsersData()
    {
        return $this->users;
    }
}
