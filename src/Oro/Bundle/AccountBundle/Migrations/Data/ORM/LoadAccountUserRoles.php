<?php

namespace Oro\Bundle\AccountBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AccountBundle\Entity\AccountUserRole;
use Oro\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\AbstractRolesData;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class LoadAccountUserRoles extends AbstractRolesData
{
    const ROLES_FILE_NAME = 'frontend_roles.yml';

    const ADMINISTRATOR = 'ADMINISTRATOR';
    const BUYER = 'BUYER';

    /** @var Website[] */
    protected $websites = [];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $aclManager = $this->getAclManager();
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');

        $roleData = $this->loadRolesData();

        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
        foreach ($roleData as $roleName => $roleConfigData) {
            $role = $this->createEntity($roleName, $roleConfigData['label']);
            if (!empty($roleConfigData['website_default_role'])) {
                $this->setWebsiteDefaultRoles($role);
            }
            $role->setOrganization($organization);
            $manager->persist($role);

            $this->setUpSelfManagedData($role, $roleConfigData);

            if (!$aclManager->isAclEnabled()) {
                continue;
            }

            $sid = $aclManager->getSid($role);

            if (!empty($roleConfigData['max_permissions'])) {
                $this->setPermissionGroup($aclManager, $sid);
            }

            if (empty($roleConfigData['permissions']) || !is_array($roleConfigData['permissions'])) {
                continue;
            }

            $this->setPermissions($aclManager, $sid, $roleConfigData['permissions']);
        }

        $chainMetadataProvider->stopProviderEmulation();

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param string $name
     * @param string $label
     * @return AccountUserRole
     */
    protected function createEntity($name, $label)
    {
        $role = new AccountUserRole(AccountUserRole::PREFIX_ROLE . $name);
        $role->setLabel($label);
        return $role;
    }

    /**
     * @param AccountUserRole $role
     */
    protected function setWebsiteDefaultRoles(AccountUserRole $role)
    {
        foreach ($this->getWebsites() as $website) {
            $role->addWebsite($website);
        }
    }

    /**
     * @return Website[]
     */
    protected function getWebsites()
    {
        if (!$this->websites) {
            $websitesIterator = $this->container->get('doctrine')
                ->getManagerForClass('OroWebsiteBundle:Website')
                ->getRepository('OroWebsiteBundle:Website')
                ->getBatchIterator();

            $this->websites = iterator_to_array($websitesIterator);
        }

        return $this->websites;
    }

    /**
     * @param AccountUserRole $role
     * @param $roleConfigData
     */
    private function setUpSelfManagedData(AccountUserRole $role, $roleConfigData)
    {
        $role->setSelfManaged(isset($roleConfigData['self_managed']) ? $roleConfigData['self_managed'] : false);
        $role->setPublic(isset($roleConfigData['public']) ? $roleConfigData['public'] : true);
    }
}
