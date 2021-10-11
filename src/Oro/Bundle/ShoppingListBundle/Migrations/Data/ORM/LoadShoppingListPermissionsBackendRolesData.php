<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * Updates DUPLICATE_SHOPPING_LIST permission for ShoppingList entity for all back-office roles.
 */
class LoadShoppingListPermissionsBackendRolesData extends AbstractUpdatePermissions
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        /** @var Role[] $roles */
        $roles = $manager->getRepository(Role::class)->findAll();
        $rolesWithAccess = ['ROLE_ADMINISTRATOR', 'ROLE_SALES_ASSISTANT'];
        foreach ($roles as $role) {
            $permission = 'DUPLICATE_SHOPPING_LIST_NONE';
            if (in_array($role->getRole(), $rolesWithAccess, true)) {
                $permission = 'DUPLICATE_SHOPPING_LIST_SYSTEM';
            }
            $this->setEntityPermissions(
                $aclManager,
                $role,
                ShoppingList::class,
                [$permission]
            );
        }
        $aclManager->flush();
    }
}
