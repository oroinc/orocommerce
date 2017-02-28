<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\AbstractLoadMultipleUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadUserData extends AbstractLoadMultipleUserData implements ContainerAwareInterface
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
                'class' => 'oro_payment.entity.payment_methods_configs_rule.class',
                'acls'  => ['VIEW_SYSTEM'],
            ],
            [
                'class' => 'oro_rule.entity.rule.class',
                'acls'  => ['VIEW_SYSTEM'],
            ],
            [
                'class' => 'oro_integration.entity.class',
                'acls'  => ['VIEW_SYSTEM'],
            ],
        ],
        self::ROLE_EDIT => [
            [
                'class' => 'oro_payment.entity.payment_methods_configs_rule.class',
                'acls'  => ['EDIT_SYSTEM'],
            ],
            [
                'class' => 'oro_integration.entity.class',
                'acls'  => ['EDIT_SYSTEM'],
            ],
        ],
        self::ROLE_CREATE => [
            [
                'class' => 'oro_payment.entity.payment_methods_configs_rule.class',
                'acls'  => ['CREATE_SYSTEM'],
            ],
            [
                'class' => 'oro_integration.entity.class',
                'acls'  => ['CREATE_SYSTEM'],
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
            'roles'     => [self::ROLE_VIEW],
        ],
        [
            'email'     => 'payment-user-editor@example.com',
            'username'  => self::USER_EDITOR,
            'password'  => self::USER_EDITOR,
            'firstname' => 'PaymentUser2FN',
            'lastname'  => 'PaymentUser2LN',
            'roles'     => [self::ROLE_EDIT],
        ],
        [
            'email'     => 'payment-user-viewer-creator@example.com',
            'username'  => self::USER_VIEWER_CREATOR,
            'password'  => self::USER_VIEWER_CREATOR,
            'firstname' => 'PaymentUser2FN',
            'lastname'  => 'PaymentUser2LN',
            'roles'     => [self::ROLE_VIEW, self::ROLE_CREATE],
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
