<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\AccountBundle\Migrations\Data\ORM\LoadAccountUserRoles;

class LoadAccountUserRolesDemoData extends LoadAccountUserRoles
{
    /** {@inheritdoc} */
    protected function getFileName($bundle)
    {
        return sprintf('@%s%s', $bundle, '/Migrations/Data/Demo/ORM/data/frontend_roles.yml');
    }
}
