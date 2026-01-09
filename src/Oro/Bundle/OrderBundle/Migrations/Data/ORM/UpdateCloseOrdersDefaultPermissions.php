<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates "CLOSE_ORDERS" default permissions for all roles.
 * It is required because a new ACL mask is added to the set of ACL masks to store this permission.
 */
class UpdateCloseOrdersDefaultPermissions extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        /** @var EntityAclExtension $entityAclExtension */
        $entityAclExtension = $aclManager->getExtensionSelector()->selectByExtensionKey(EntityAclExtension::NAME);
        $maskBuilder = $this->getRootMaskBuilder($entityAclExtension);
        if (null === $maskBuilder) {
            return;
        }

        $roles = $manager->getRepository(Role::class)->findAll();
        foreach ($roles as $role) {
            $aclManager->setPermission(
                $aclManager->getSid($role),
                $aclManager->getRootOid($entityAclExtension->getExtensionKey()),
                $this->getRootOidMask($role, $maskBuilder)
            );
        }
        $aclManager->flush();
    }

    private function getRootMaskBuilder(EntityAclExtension $entityAclExtension): ?MaskBuilder
    {
        $maskBuilders = $entityAclExtension->getAllMaskBuilders();
        foreach ($maskBuilders as $maskBuilder) {
            if ($maskBuilder->hasMaskForPermission('CLOSE_ORDERS_SYSTEM')) {
                return $maskBuilder;
            }
        }

        return null;
    }

    private function getRootOidMask(Role $role, MaskBuilder $maskBuilder): int
    {
        if ($role->getRole() === User::ROLE_ADMINISTRATOR) {
            return $maskBuilder->hasMaskForGroup('SYSTEM')
                ? $maskBuilder->getMaskForGroup('SYSTEM')
                : $maskBuilder->getMaskForGroup('ALL');
        }

        return $maskBuilder->getMaskForGroup('NONE');
    }
}
