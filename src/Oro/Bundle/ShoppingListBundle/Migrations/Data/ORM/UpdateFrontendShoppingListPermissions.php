<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Updates permissions for ShoppingList entity for ROLE_FRONTEND_ANONYMOUS, ROLE_FRONTEND_ADMINISTRATOR storefront role.
 */
class UpdateFrontendShoppingListPermissions extends AbstractUpdatePermissions implements DependentFixtureInterface
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
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $this->updatePermission($manager, 'RENAME_SHOPPING_LIST');
        $this->updatePermission($manager, 'SET_AS_DEFAULT_SHOPPING_LIST');
    }

    private function updatePermission(ObjectManager $manager, string $permission): void
    {
        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $roles = $manager->getRepository(CustomerUserRole::class)->findAll();
        $oidDescriptor = ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, ShoppingList::class);
        $oid = $aclManager->getOid($oidDescriptor);
        $maskBuilder = $aclManager->getMaskBuilder($oid, $permission);
        foreach ($roles as $role) {
            $sid = $aclManager->getSid($role);
            $allRolePrivileges = $this->getPrivileges($sid);
            foreach ($allRolePrivileges as $aclPrivilege) {
                if ($aclPrivilege->getIdentity()->getId() !== $oidDescriptor) {
                    continue;
                }

                $accessLevelName = $this->getPermissionAccessLevelName($aclPrivilege, 'EDIT');
                if (!$accessLevelName) {
                    $maskBuilder->reset();
                    $aclManager->setPermission($sid, $oid, $maskBuilder->get());
                    continue;
                }

                $mask = $permission . '_' . $accessLevelName;
                if ($maskBuilder->hasMaskForPermission($mask)) {
                    $maskBuilder->reset();
                    $maskBuilder->add($mask);
                    $aclManager->setPermission($sid, $oid, $maskBuilder->get());
                }
            }
        }
        $aclManager->flush();
    }
}
