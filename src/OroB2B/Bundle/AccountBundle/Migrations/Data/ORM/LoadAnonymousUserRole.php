<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\ORM;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class LoadAnonymousUserRole extends LoadAccountUserRoles
{
    const ROLES_FILE_NAME = 'anonymous_role.yml';

    /**
     * @param string $name
     * @param string $label
     * @return AccountUserRole
     */
    protected function createEntity($name, $label)
    {
        $role = new AccountUserRole($name);
        $role->setLabel($label);
        return $role;
    }
}
