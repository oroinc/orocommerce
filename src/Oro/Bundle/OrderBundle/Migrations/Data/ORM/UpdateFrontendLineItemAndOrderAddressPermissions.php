<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractMassUpdateCustomerUserRolePermissions;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Update OrderLineItem default permissions for predefined roles.
 */
class UpdateFrontendLineItemAndOrderAddressPermissions extends AbstractMassUpdateCustomerUserRolePermissions
{
    /**
     * {@inheritdoc}
     */
    protected function getACLData(): array
    {
        return [
            'ROLE_FRONTEND_ADMINISTRATOR' => [
                'entity:' . OrderLineItem::class => ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM'],
                'entity:' . OrderAddress::class => ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM']
            ],
            'ROLE_FRONTEND_BUYER' => [
                'entity:' . OrderLineItem::class => ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM'],
                'entity:' . OrderAddress::class => ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM']
            ],
            'ROLE_FRONTEND_ANONYMOUS' => [
                'entity:' . OrderLineItem::class => ['VIEW_NONE', 'CREATE_NONE', 'EDIT_NONE', 'DELETE_NONE'],
                'entity:' . OrderAddress::class => ['VIEW_NONE', 'CREATE_NONE', 'EDIT_NONE', 'DELETE_NONE']
            ]
        ];
    }
}
