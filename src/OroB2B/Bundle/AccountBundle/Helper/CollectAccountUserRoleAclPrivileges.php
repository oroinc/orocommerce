<?php

namespace OroB2B\Bundle\AccountBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class CollectAccountUserRoleAclPrivileges
{
    /**
     * @var AclPrivilegeRepository
     */
    protected $aclPrivilegeRepository;

    /**
     * @var AclManager
     */
    protected $aclManager;

    /**
     * ['<extension_key>' => ['<allowed_group>', ...], ...]
     *
     * @var array
     */
    protected $extensionFilters = [];

    /**
     * @var array
     */
    protected $privilegeConfig;

    /**
     * @var ChainMetadataProvider
     */
    protected $chainMetadataProvider;

    /**
     * @param AclPrivilegeRepository $aclPrivilegeRepository
     * @param AclManager             $aclManager
     * @param ChainMetadataProvider  $chainMetadataProvider
     * @param array                  $privilegeConfig
     */
    public function __construct(
        AclPrivilegeRepository $aclPrivilegeRepository,
        AclManager $aclManager,
        ChainMetadataProvider $chainMetadataProvider,
        $privilegeConfig
    ) {
        $this->aclPrivilegeRepository = $aclPrivilegeRepository;
        $this->aclManager = $aclManager;
        $this->chainMetadataProvider = $chainMetadataProvider;
        $this->setPrivilegeConfigs($privilegeConfig);

    }

    /**
     * @param AccountUserRole $accountUserRole
     * @return array
     */
    public function collect(AccountUserRole $accountUserRole)
    {
        $privileges = $this->getPrivileges($accountUserRole);

        return [
            'data' => $this->filterAndSortedPrivileges($privileges),
            'privilegesConfig' => $this->getPrivilegeConfig(),
            'accessLevelNames' => AccessLevel::$allAccessLevelNames
        ];
    }

    /**
     * @param AccountUserRole $accountUserRole
     * @return ArrayCollection|AclPrivilege[]
     */
    protected function getPrivileges($accountUserRole)
    {
        $this->startFrontendProviderEmulation();
        $privileges = $this->aclPrivilegeRepository->getPrivileges($this->aclManager->getSid($accountUserRole));
        $this->stopFrontendProviderEmulation();

        return $privileges;
    }

    /**
     * @param ArrayCollection|AclPrivilege[] $privileges
     * @return array
     */
    protected function filterAndSortedPrivileges($privileges)
    {
        $sortedPrivileges = [];
        foreach ($this->privilegeConfig as $fieldName => $config) {
            $sortedPrivileges[$fieldName] = $this->filterPrivileges($privileges, $config['types']);
            if ($config['fix_values'] || !$config['show_default']) {
                /** @var AclPrivilege $sortedPrivilege */
                foreach ($sortedPrivileges[$fieldName] as $key => $sortedPrivilege) {
                    if (!$config['show_default']
                        && $sortedPrivilege->getIdentity()->getName() == AclPrivilegeRepository::ROOT_PRIVILEGE_NAME) {
                        unset($sortedPrivileges[$fieldName][$key]);
                        continue;
                    }
                    if ($config['fix_values']) {
                        foreach ($sortedPrivilege->getPermissions() as $permission) {
                            $permission->setAccessLevel((bool)$permission->getAccessLevel());
                        }
                    }
                }
            }
        }
        return $sortedPrivileges;
    }

    /**
     * @param ArrayCollection $privileges
     * @param array $rootIds
     * @return ArrayCollection|AclPrivilege[]
     */
    protected function filterPrivileges(ArrayCollection $privileges, array $rootIds)
    {
        return $privileges->filter(
            function (AclPrivilege $entry) use ($rootIds) {
                $extensionKey = $entry->getExtensionKey();

                // only current extension privileges
                if (!in_array($extensionKey, $rootIds, true)) {
                    return false;
                }

                // not filtered are allowed
                if (!array_key_exists($extensionKey, $this->extensionFilters)) {
                    return true;
                }

                // filter by groups
                return in_array($entry->getGroup(), $this->extensionFilters[$extensionKey], true);
            }
        );
    }

    /**
     * @param string $extensionKey
     * @param string $allowedGroup
     */
    public function addExtensionFilter($extensionKey, $allowedGroup)
    {
        if (!array_key_exists($extensionKey, $this->extensionFilters)) {
            $this->extensionFilters[$extensionKey] = [];
        }

        if (!in_array($allowedGroup, $this->extensionFilters[$extensionKey])) {
            $this->extensionFilters[$extensionKey][] = $allowedGroup;
        }
    }

    /**
     * @param array $privilegeConfig
     */
    public function setPrivilegeConfigs($privilegeConfig)
    {
        $this->privilegeConfig = $privilegeConfig;
        foreach ($this->privilegeConfig as $configName => $config) {
            $this->privilegeConfig[$configName]['permissions']
                = $this->aclPrivilegeRepository->getPermissionNames($config['types']);
        }
    }

    protected function startFrontendProviderEmulation()
    {
        if ($this->chainMetadataProvider) {
            $this->chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);
        }
    }

    protected function stopFrontendProviderEmulation()
    {
        if ($this->chainMetadataProvider) {
            $this->chainMetadataProvider->stopProviderEmulation();
        }
    }

    /**
     * @return array
     */
    private function getPrivilegeConfig()
    {
        if (isset($this->privilegeConfig['entity']['permissions'])) {
            if (in_array('SHARE', $this->privilegeConfig['entity']['permissions'])) {
                array_pop($this->privilegeConfig['entity']['permissions']);
            }
        }
        return $this->privilegeConfig;
    }
}
