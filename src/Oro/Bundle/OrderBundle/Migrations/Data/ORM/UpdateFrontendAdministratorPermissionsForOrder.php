<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractUpdateCustomerUserRolePermissions;
use Oro\Bundle\OrderBundle\Entity\Order;

class UpdateFrontendAdministratorPermissionsForOrder extends AbstractUpdateCustomerUserRolePermissions
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
        return 'entity:' . Order::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissions()
    {
        return ['VIEW_DEEP', 'CREATE_DEEP', 'EDIT_DEEP', 'ASSIGN_DEEP'];
    }
}
