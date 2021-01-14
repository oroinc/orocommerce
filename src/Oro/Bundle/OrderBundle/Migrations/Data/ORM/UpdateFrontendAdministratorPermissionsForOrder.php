<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;

/**
 * Updates permissions for Order entity for ROLE_FRONTEND_ADMINISTRATOR storefront role.
 */
class UpdateFrontendAdministratorPermissionsForOrder extends AbstractUpdatePermissions implements
    DependentFixtureInterface
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
            $this->getRole($manager, 'ROLE_FRONTEND_ADMINISTRATOR', CustomerUserRole::class),
            Order::class,
            ['VIEW_DEEP', 'CREATE_DEEP', 'EDIT_DEEP', 'ASSIGN_DEEP']
        );
        $aclManager->flush();
    }
}
