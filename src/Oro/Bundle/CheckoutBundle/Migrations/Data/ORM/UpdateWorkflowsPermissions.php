<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\AbstractMassUpdateCustomerUserRolePermissions;

/**
 * Update workflow default permissions for predefined roles.
 */
class UpdateWorkflowsPermissions extends AbstractMassUpdateCustomerUserRolePermissions
{
    /**
     * {@inheritdoc}
     */
    protected function getACLData(): array
    {
        return [
            'ROLE_FRONTEND_ADMINISTRATOR' => [
                'workflow:b2b_flow_checkout' => ['VIEW_WORKFLOW_DEEP', 'PERFORM_TRANSITIONS_DEEP'],
                'workflow:b2b_flow_checkout_single_page' => ['VIEW_WORKFLOW_DEEP', 'PERFORM_TRANSITIONS_DEEP']
            ],
            'ROLE_FRONTEND_BUYER' => [
                'workflow:b2b_flow_checkout' => ['VIEW_WORKFLOW_BASIC', 'PERFORM_TRANSITIONS_BASIC'],
                'workflow:b2b_flow_checkout_single_page' => ['VIEW_WORKFLOW_BASIC', 'PERFORM_TRANSITIONS_BASIC']
            ],
            'ROLE_FRONTEND_ANONYMOUS' => [
                'workflow:b2b_flow_checkout' => ['VIEW_WORKFLOW_BASIC', 'PERFORM_TRANSITIONS_BASIC'],
                'workflow:b2b_flow_checkout_single_page' => ['VIEW_WORKFLOW_BASIC', 'PERFORM_TRANSITIONS_BASIC']
            ]
        ];
    }
}
