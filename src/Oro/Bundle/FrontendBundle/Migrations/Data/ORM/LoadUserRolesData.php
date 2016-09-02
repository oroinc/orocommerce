<?php

namespace Oro\Bundle\FrontendBundle\Migrations\Data\ORM;

use Oro\Bundle\UserBundle\Entity\Role;

class LoadUserRolesData extends AbstractRolesData
{
    const ROLES_FILE_NAME = 'backend_roles.yml';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData'];
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($name, $label)
    {
        $entity = new Role($name);
        $entity->setLabel($label);

        return $entity;
    }
}
