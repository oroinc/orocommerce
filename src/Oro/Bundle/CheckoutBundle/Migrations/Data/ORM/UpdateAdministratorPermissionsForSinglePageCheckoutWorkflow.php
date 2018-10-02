<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractUpdateCustomerUserRolePermissions;

/**
 * Set Corporate (All Levels) permission for Single Page Checkout workflow for Administrator
 */
class UpdateAdministratorPermissionsForSinglePageCheckoutWorkflow extends AbstractUpdateCustomerUserRolePermissions
{
    /**
     * {@inheritdoc}
     */
    protected function getRoleName()
    {
        return 'ROLE_FRONTEND_ADMINISTRATOR';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityOid()
    {
        return 'workflow:b2b_flow_checkout_single_page';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissions()
    {
        return ['VIEW_WORKFLOW_DEEP', 'PERFORM_TRANSITIONS_DEEP'];
    }
}
