<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadCustomerUserRoles;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;

class LoadCustomerBuyerRoles extends LoadCustomerUserRoles
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->hasParameter('installed') || !$this->container->getParameter('installed')) {
            return;
        }
        
        $aclManager = $this->getAclManager();
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');
        $roleData = $this->loadRolesData(['OroCheckoutBundle']);
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        foreach ($roleData as $roleName => $roleConfigData) {
            $role = $manager->getRepository('OroCustomerBundle:CustomerUserRole')
                ->findOneBy([
                    'role' => sprintf('%s%s', CustomerUserRole::PREFIX_ROLE, $roleName),
                    'organization' => $organization
                ]);
            if (!$role) {
                continue;
            }
            if (!$aclManager->isAclEnabled()) {
                continue;
            }
            $sid = $aclManager->getSid($role);
            if (empty($roleConfigData['permissions']) || !is_array($roleConfigData['permissions'])) {
                continue;
            }
            if (!$this->roleHasEntries($roleConfigData['permissions'], $aclManager, $sid)) {
                $this->setPermissions($aclManager, $sid, $roleConfigData['permissions']);
            }
        }
        $chainMetadataProvider->stopProviderEmulation();
        $aclManager->flush();
    }

    /**
     * @param array      $permissions
     * @param AclManager $aclManager
     * @param SID        $sid
     *
     * @return boolean
     */
    protected function roleHasEntries(array $permissions, AclManager $aclManager, SID $sid)
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
