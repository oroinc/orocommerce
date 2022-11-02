<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Updates permissions for ShoppingList entity for ROLE_FRONTEND_ANONYMOUS storefront role.
 */
class UpdateAnonymousPermissionsForShoppingList extends AbstractUpdatePermissions implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadCustomerUserRoles::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $this->setEntityPermissions(
            $aclManager,
            $this->getRole($manager, 'ROLE_FRONTEND_ANONYMOUS', CustomerUserRole::class),
            ShoppingList::class,
            ['VIEW_BASIC', 'EDIT_BASIC']
        );
        $aclManager->flush();
    }
}
