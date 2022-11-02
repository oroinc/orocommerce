<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Updates permissions for LineItem entity for ROLE_FRONTEND_ANONYMOUS storefront role.
 */
class UpdateAnonymousPermissionsForLineItem extends AbstractUpdatePermissions implements DependentFixtureInterface
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
            LineItem::class,
            ['VIEW_BASIC', 'EDIT_BASIC', 'DELETE_BASIC']
        );
        $aclManager->flush();
    }
}
