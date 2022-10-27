<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Updates DUPLICATE_SHOPPING_LIST permission for ShoppingList entity for all storefront roles.
 */
class LoadShoppingListPermissionsRolesData extends AbstractUpdatePermissions
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

        /** @var CustomerUserRole[] $roles */
        $roles = $manager->getRepository(CustomerUserRole::class)->findAll();
        foreach ($roles as $role) {
            if ($role->getRole() === 'ROLE_FRONTEND_ANONYMOUS') {
                continue;
            }
            $permission = 'DUPLICATE_SHOPPING_LIST_BASIC';
            if ($role->getRole() === 'ROLE_FRONTEND_ADMINISTRATOR') {
                $permission = 'DUPLICATE_SHOPPING_LIST_DEEP';
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
