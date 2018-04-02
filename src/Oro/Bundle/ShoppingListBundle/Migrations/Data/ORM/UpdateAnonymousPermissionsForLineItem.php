<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractUpdateCustomerUserRolePermissions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class UpdateAnonymousPermissionsForLineItem extends AbstractUpdateCustomerUserRolePermissions
{
    /**
     * {@inheritdoc}
     */
    protected function getRoleName()
    {
        return 'ROLE_FRONTEND_ANONYMOUS';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityOid()
    {
        return 'entity:' . LineItem::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissions()
    {
        return ['VIEW_BASIC', 'EDIT_BASIC', 'DELETE_BASIC'];
    }
}
