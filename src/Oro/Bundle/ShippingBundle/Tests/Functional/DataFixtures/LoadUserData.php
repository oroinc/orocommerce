<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\AbstractLoadMultipleUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadUserData extends AbstractLoadMultipleUserData implements ContainerAwareInterface
{
    const USER_VIEWER = 'shipping-user-viewer';
    const USER_EDITOR = 'shipping-user-editor';
    const USER_VIEWER_CREATOR = 'shipping-user-viewer-creator';

    const ROLE_VIEW = 'SHIPPING_ROLE_VIEW';
    const ROLE_EDIT = 'SHIPPING_ROLE_EDIT';
    const ROLE_CREATE = 'SHIPPING_ROLE_CREATE';

    /**
     * @var array
     */
    protected $roles = [
        self::ROLE_VIEW => [
            [
                'class' => 'oro_shipping.entity.shipping_methods_configs_rule.class',
                'acls'  => ['VIEW_SYSTEM'],
            ],
            [
                'class' => 'oro_rule.entity.rule.class',
                'acls'  => ['VIEW_SYSTEM'],
            ],
        ],
        self::ROLE_EDIT => [
            [
                'class' => 'oro_shipping.entity.shipping_methods_configs_rule.class',
                'acls'  => ['EDIT_SYSTEM'],
            ],
        ],
        self::ROLE_CREATE => [
            [
                'class' => 'oro_shipping.entity.shipping_methods_configs_rule.class',
                'acls'  => ['CREATE_SYSTEM'],
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $users = [
        [
            'email'     => 'shipping-user-viewer@example.com',
            'username'  => self::USER_VIEWER,
            'password'  => self::USER_VIEWER,
            'firstname' => 'ShippingUser1FN',
            'lastname'  => 'ShippingUser1LN',
            'roles'     => [self::ROLE_VIEW],
        ],
        [
            'email'     => 'shipping-user-editor@example.com',
            'username'  => self::USER_EDITOR,
            'password'  => self::USER_EDITOR,
            'firstname' => 'ShippingUser2FN',
            'lastname'  => 'ShippingUser2LN',
            'roles'     => [self::ROLE_EDIT],
        ],
        [
            'email'     => 'shipping-user-viewer-creator@example.com',
            'username'  => self::USER_VIEWER_CREATOR,
            'password'  => self::USER_VIEWER_CREATOR,
            'firstname' => 'ShippingUser2FN',
            'lastname'  => 'ShippingUser2LN',
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
