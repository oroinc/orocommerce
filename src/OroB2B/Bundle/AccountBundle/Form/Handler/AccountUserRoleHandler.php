<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleType;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class AccountUserRoleHandler extends AclRoleHandler
{
    /**
     * @var ConfigProviderInterface
     */
    protected $ownershipConfigProvider;

    /**
     * @var ChainMetadataProvider
     */
    protected $chainMetadataProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AccountUserRole
     */
    protected $originalRole;

    /**
     * @param ConfigProviderInterface $provider
     */
    public function setOwnershipConfigProvider(ConfigProviderInterface $provider)
    {
        $this->ownershipConfigProvider = $provider;
    }

    /**
     * @param ChainMetadataProvider $chainMetadataProvider
     */
    public function setChainMetadataProvider(ChainMetadataProvider $chainMetadataProvider)
    {
        $this->chainMetadataProvider = $chainMetadataProvider;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRoleFormInstance(AbstractRole $role, array $privilegeConfig)
    {
        return $this->formFactory->create(
            AccountUserRoleType::NAME,
            $role,
            ['privilege_config' => $privilegeConfig]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function filterPrivileges(ArrayCollection $privileges, array $rootIds)
    {
        $privileges = parent::filterPrivileges($privileges, $rootIds);

        $entityPrefix = 'entity:';

        foreach ($privileges as $key => $privilege) {
            $oid = $privilege->getIdentity()->getId();
            if (strpos($oid, $entityPrefix) === 0) {
                $className = substr($oid, strlen($entityPrefix));
                if (!$this->hasFrontendOwnership($className)) {
                    unset($privileges[$key]);
                }
            }
        }

        return $privileges;
    }

    /**
     * @param string $className
     * @return bool
     */
    protected function hasFrontendOwnership($className)
    {
        if ($this->ownershipConfigProvider->hasConfig($className)) {
            $config = $this->ownershipConfigProvider->getConfig($className);
            if ($config->has('frontend_owner_type')) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRolePrivileges(AbstractRole $role)
    {
        $this->startFrontendProviderEmulation();
        $privileges = parent::getRolePrivileges($role);
        $this->stopFrontendProviderEmulation();

        return $privileges;
    }

    /**
     * {@inheritdoc}
     */
    protected function processPrivileges(AbstractRole $role)
    {
        $this->startFrontendProviderEmulation();
        parent::processPrivileges($role);
        $this->stopFrontendProviderEmulation();
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
     * {@inheritDoc}
     */
    protected function getAclGroup()
    {
        return AccountUser::SECURITY_GROUP;
    }

    /**
     * {@inheritDoc}
     */
    protected function onSuccess(AbstractRole $role, array $appendUsers, array $removeUsers)
    {
        $this->fixUsersByAccount($role, $appendUsers, $removeUsers);
        parent::onSuccess($role, $appendUsers, $removeUsers);
    }

    /**
     * {@inheritDoc}
     */
    public function process(AbstractRole $role)
    {
        $this->originalRole = clone $role;

        return parent::process($role);
    }

    /**
     * @param AccountUserRole|AbstractRole $role
     * @param array                        $appendUsers
     * @param array                        $removeUsers
     */
    protected function fixUsersByAccount(AccountUserRole $role, array &$appendUsers, array &$removeUsers)
    {
        /** @var AccountUserRoleRepository $roleRepository */
        $roleRepository = $this->doctrineHelper->getEntityRepository($role);

        // Role moved to another account OR account added
        if ($role->getId() && (
                ($this->originalRole->getAccount() !== $role->getAccount() &&
                    $this->originalRole->getAccount() !== null && $role->getAccount() !== null) ||
                ($this->originalRole->getAccount() === null && $role->getAccount() !== null)
            )
        ) {
            // Remove assigned users
            $assignedUsers = $roleRepository->getAssignedUsers($role);
            $removeUsers = array_replace($removeUsers, $assignedUsers);

            $appendNewUsers = array_diff($appendUsers, $removeUsers);
            $removeNewUsers = array_diff($removeUsers, $appendUsers);

            $removeUsers = $removeNewUsers;
            $appendUsers = $appendNewUsers;
        }

        if ($role->getAccount()) {
            // Security check
            $appendUsers = array_filter(
                $appendUsers,
                function (AccountUser $user) use ($role) {
                    return $user->getAccount() === $role->getAccount();
                }
            );
        }
    }
}
