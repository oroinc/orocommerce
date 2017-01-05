<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;

class LoadAnonymousUserRole extends LoadCustomerUserRoles
{
    const ROLES_FILE_NAME = 'anonymous_role.yml';

    /**
     * @param string $name
     * @param string $label
     * @return CustomerUserRole
     */
    protected function createEntity($name, $label)
    {
        $role = new CustomerUserRole($name);
        $role->setLabel($label);
        $role->setSelfManaged(false);
        return $role;
    }
}
