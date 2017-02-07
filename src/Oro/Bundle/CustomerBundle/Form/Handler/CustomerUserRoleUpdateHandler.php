<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class CustomerUserRoleUpdateHandler extends AbstractCustomerUserRoleHandler
{
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
