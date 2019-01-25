<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractMassUpdateCustomerUserRolePermissions;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

/**
 * Update RequestProduct and RequestProductItem default permissions for predefined roles.
 */
class UpdateFrontendItemPermissions extends AbstractMassUpdateCustomerUserRolePermissions
{
    /**
     * {@inheritdoc}
     */
    protected function getACLData(): array
    {
        return [
            'ROLE_FRONTEND_ADMINISTRATOR' => [
                'entity:' . RequestProduct::class => ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM'],
                'entity:' . RequestProductItem::class => [
                    'VIEW_SYSTEM',
                    'CREATE_SYSTEM',
                    'EDIT_SYSTEM',
                    'DELETE_SYSTEM'
                ]
            ],
            'ROLE_FRONTEND_BUYER' => [
                'entity:' . RequestProduct::class => ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM'],
                'entity:' . RequestProductItem::class => [
                    'VIEW_SYSTEM',
                    'CREATE_SYSTEM',
                    'EDIT_SYSTEM',
                    'DELETE_SYSTEM'
                ]
            ],
            'ROLE_FRONTEND_ANONYMOUS' => [
                'entity:' . RequestProduct::class => ['VIEW_NONE', 'CREATE_NONE', 'EDIT_NONE', 'DELETE_NONE'],
                'entity:' . RequestProductItem::class => ['VIEW_NONE', 'CREATE_NONE', 'EDIT_NONE', 'DELETE_NONE']
            ]
        ];
    }
}
