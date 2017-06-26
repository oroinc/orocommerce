<?php

namespace Oro\Bundle\CheckountBundle\Migrations\Data\ORM;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractUpdateCustomerUserRolePermissions;

class UpdateFrontendAdministratorPermissionsForCheckout extends AbstractUpdateCustomerUserRolePermissions
{
    /**
     * {@inheritdoc}
     */
    protected function getRoleName()
    {
        return 'ROLE_FRONTEND_ADMINISTRATOR';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityOid()
    {
        return 'entity:' . Checkout::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissions()
    {
        return ['VIEW_DEEP', 'CREATE_DEEP', 'EDIT_DEEP', 'DELETE_DEEP', 'ASSIGN_DEEP'];
    }
}
