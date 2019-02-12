<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Workflow;

class QuoteBackofficeDefaultWorkflowTest extends BaseQuoteBackofficeWorkflowTestCase
{
    const WORKFLOW_NAME = 'b2b_quote_backoffice_default';

    const WORKFLOW_TITLE = 'Quote Management Flow';

    const WORKFLOW_BUTTONS = [
        'Edit',
        'Clone',
        'Delete',
        'Undelete',
        'Send to Customer',
        'Cancel',
        'Expire',
        'Create new Quote',
        'Accept',
        'Decline',
        'Decline by Customer',
        'Reopen',
    ];

    const TRANSITIONS = [
        'edit_transition',
        'clone_transition',
        'delete_transition',
        'undelete_transition',
        'send_to_customer_transition',
        'cancel_transition',
        'expire_transition',
        'auto_expire_transition',
        'create_new_quote_transition',
        'accept_transition',
        'decline_transition',
        'decline_by_customer_transition',
        'reopen_transition',
        '__start__',
    ];

    public function testApplicableWorkflows()
    {
        $this->assertApplicableWorkflows();
    }

    protected function activateWorkflow()
    {
        $this->manager->deactivateWorkflow('b2b_quote_backoffice_approvals');
        $this->manager->resetWorkflowData('b2b_quote_backoffice_approvals');

        parent::activateWorkflow();
    }
}
