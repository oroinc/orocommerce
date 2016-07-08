<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UserBundle\Entity\AbstractRole;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class AccountUserRoleUpdateHandler extends AbstractAccountUserRoleHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack($requestStack)
    {
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getCurrentRequest();
    }
    
    /**
     * @var Account
     */
    protected $originalAccount;

    /**
     * {@inheritDoc}
     */
    protected function onSuccess(AbstractRole $role, array $appendUsers, array $removeUsers)
    {
        $this->applyAccountLimits($role, $appendUsers, $removeUsers);

        parent::onSuccess($role, $appendUsers, $removeUsers);
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
     * @param AccountUserRole|AbstractRole $role
     * @param array                        $appendUsers
     * @param array                        $removeUsers
     */
    protected function applyAccountLimits(AccountUserRole $role, array &$appendUsers, array &$removeUsers)
    {
        /** @var AccountUserRoleRepository $roleRepository */
        $roleRepository = $this->doctrineHelper->getEntityRepository($role);

        // Role moved to another account OR account added
        if ($role->getId() && (
                ($this->originalAccount !== $role->getAccount() &&
                    $this->originalAccount !== null && $role->getAccount() !== null) ||
                ($this->originalAccount === null && $role->getAccount() !== null)
            )
        ) {
            // Remove assigned users
            $assignedUsers = $roleRepository->getAssignedUsers($role);

            $removeUsers = array_replace(
                $removeUsers,
                array_filter(
                    $assignedUsers,
                    function (AccountUser $accountUser) use ($role) {
                        return $accountUser->getAccount() !== $role->getAccount();
                    }
                )
            );

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

    /**
     * {@inheritDoc}
     */
    public function process(AbstractRole $role)
    {
        if ($role instanceof AccountUserRole) {
            $this->originalAccount = $role->getAccount();
        }

        return parent::process($role);
    }
}
