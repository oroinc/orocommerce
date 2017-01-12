<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class CustomerUserRoleUpdateHandler extends AbstractCustomerUserRoleHandler
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
     * @var Customer
     */
    protected $originalCustomer;

    /**
     * {@inheritDoc}
     */
    protected function onSuccess(AbstractRole $role, array $appendUsers, array $removeUsers)
    {
        $this->applyCustomerLimits($role, $appendUsers, $removeUsers);

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
    protected function processPrivileges(AbstractRole $role, $className = null)
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
     * @param CustomerUserRole|AbstractRole $role
     * @param array                        $appendUsers
     * @param array                        $removeUsers
     */
    protected function applyCustomerLimits(CustomerUserRole $role, array &$appendUsers, array &$removeUsers)
    {
        /** @var CustomerUserRoleRepository $roleRepository */
        $roleRepository = $this->doctrineHelper->getEntityRepository($role);

        // Role moved to another customer OR customer added
        if ($role->getId() && (
                ($this->originalCustomer !== $role->getCustomer() &&
                    $this->originalCustomer !== null && $role->getCustomer() !== null) ||
                ($this->originalCustomer === null && $role->getCustomer() !== null)
            )
        ) {
            // Remove assigned users
            $assignedUsers = $roleRepository->getAssignedUsers($role);

            $removeUsers = array_replace(
                $removeUsers,
                array_filter(
                    $assignedUsers,
                    function (CustomerUser $customerUser) use ($role) {
                        return $customerUser->getCustomer() !== $role->getCustomer();
                    }
                )
            );

            $appendNewUsers = array_diff($appendUsers, $removeUsers);
            $removeNewUsers = array_diff($removeUsers, $appendUsers);

            $removeUsers = $removeNewUsers;
            $appendUsers = $appendNewUsers;
        }

        if ($role->getCustomer()) {
            // Security check
            $appendUsers = array_filter(
                $appendUsers,
                function (CustomerUser $user) use ($role) {
                    return $user->getCustomer() === $role->getCustomer();
                }
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function process(AbstractRole $role)
    {
        if ($role instanceof CustomerUserRole) {
            $this->originalCustomer = $role->getCustomer();
        }

        return parent::process($role);
    }
}
