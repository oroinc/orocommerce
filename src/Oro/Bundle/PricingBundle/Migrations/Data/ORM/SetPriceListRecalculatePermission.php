<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * Copies EDIT permission to RECALCULATE permission for PriceList entity for all roles.
 */
class SetPriceListRecalculatePermission extends AbstractUpdatePermissions
{
    public function load(ObjectManager $manager)
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $roles = $manager->getRepository(Role::class)->findAll();
        $oidDescriptor = ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, PriceList::class);
        $oid = $aclManager->getOid($oidDescriptor);
        $maskBuilder = $aclManager->getMaskBuilder($oid, 'RECALCULATE');
        foreach ($roles as $role) {
            $sid = $aclManager->getSid($role);
            $allRolePrivileges = $this->getPrivileges($sid);
            foreach ($allRolePrivileges as $aclPrivilege) {
                if ($aclPrivilege->getIdentity()->getId() !== $oidDescriptor) {
                    continue;
                }
                $permission = 'RECALCULATE_' . $this->getPermissionAccessLevelName($aclPrivilege, 'EDIT');
                if ($maskBuilder->hasMaskForPermission($permission)) {
                    $maskBuilder->add($permission);
                    $aclManager->setPermission($sid, $oid, $maskBuilder->get());
                }
            }
        }
        $aclManager->flush();
    }
}
