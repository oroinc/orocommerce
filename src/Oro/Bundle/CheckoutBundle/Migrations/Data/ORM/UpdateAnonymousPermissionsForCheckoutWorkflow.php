<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractUpdateCustomerUserRolePermissions;

/**
 * Set User (Own) permission for Checkout workflow for Anonymous
 */
class UpdateAnonymousPermissionsForCheckoutWorkflow extends AbstractUpdateCustomerUserRolePermissions
{
    /**
     * {@inheritdoc}
     */
    protected function getRoleName()
    {
        return 'ROLE_FRONTEND_ANONYMOUS';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityOid()
    {
        return 'workflow:b2b_flow_checkout';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissions()
    {
        return ['VIEW_WORKFLOW_BASIC', 'PERFORM_TRANSITIONS_BASIC'];
    }
}
