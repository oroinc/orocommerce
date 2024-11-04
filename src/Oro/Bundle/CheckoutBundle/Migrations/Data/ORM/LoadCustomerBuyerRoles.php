<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainOwnershipMetadataProvider;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;

/**
 * Updates checkout related permissions for storefront roles.
 */
class LoadCustomerBuyerRoles extends LoadCustomerUserRoles
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $organization = $manager->getRepository(Organization::class)->findOneBy([]);
        $roleData = $this->loadRolesData(['OroCheckoutBundle']);

        /* @var ChainOwnershipMetadataProvider $chainMetadataProvider */
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        foreach ($roleData as $roleName => $roleConfigData) {
            $role = $manager->getRepository(CustomerUserRole::class)
                ->findOneBy([
                    'role' => sprintf('%s%s', CustomerUserRole::PREFIX_ROLE, $roleName),
                    'organization' => $organization
                ]);
            if (null === $role) {
                continue;
            }

            $sid = $aclManager->getSid($role);
            $permissions = $roleConfigData['permissions'] ?? [];
            if ($permissions && !$this->roleHasEntries($permissions, $aclManager, $sid)) {
                $this->setPermissions($aclManager, $sid, $permissions);
            }
        }

        $chainMetadataProvider->stopProviderEmulation();

        $aclManager->flush();
    }

    private function roleHasEntries(array $permissions, AclManager $aclManager, SID $sid): bool
    {
        foreach ($permissions as $permission => $acls) {
            $oid = $aclManager->getOid(str_replace('|', ':', $permission));
            if ($aclManager->getAces($sid, $oid)) {
                return true;
            }
        }

        return false;
    }
}
