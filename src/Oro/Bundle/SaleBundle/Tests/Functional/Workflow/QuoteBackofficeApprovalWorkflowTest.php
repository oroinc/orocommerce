<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Workflow;

use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\User;

class QuoteBackofficeApprovalWorkflowTest extends BaseQuoteBackofficeWorkflowTestCase
{
    use RolePermissionExtension;

    const WORKFLOW_NAME = 'b2b_quote_backoffice_approvals';
    const WORKFLOW_TITLE = 'Backoffice Quote Flow with Approvals';
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
        'Submit for Review',
        'Review',
        'Return',
        'Decline',
        'Approve and Send to Customer',
        'Approve',
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
        'submit_for_review_transition',
        'review_transition',
        'return_transition',
        'approve_and_send_to_customer_transition',
        'approve_transition',
        'decline_by_reviewer_transition',
        '__start__',
    ];

    public function testApplicableWorkflows()
    {
        $this->assertApplicableWorkflows();
    }

    public function testReviewReturn()
    {
        $this->assertSubmitForReviewAndReview();
        $this->transitWithForm('Return', ['oro_workflow_transition[comment]' => 'test submit comment']);
        $this->updateRolePermissionForAction(User::ROLE_ADMINISTRATOR, 'oro_quote_review_and_approve', false);
        $this->assertButtonsAvailable(['Edit', 'Clone', 'Delete', 'Submit for Review', 'Send to Customer']);
    }

    public function testReviewSendToCustomer()
    {
        $this->assertSubmitForReviewAndReview();
        $this->transitWithForm(
            'Approve and Send to Customer',
            ['oro_workflow_transition[email][to]' => 'test_email@test.tst']
        );
        $this->assertStatuses('sent_to_customer', 'open');
        $this->updateRolePermissionForAction(User::ROLE_ADMINISTRATOR, 'oro_quote_review_and_approve', false);
        $this->assertButtonsAvailable(['Expire', 'Cancel', 'Delete', 'Create new Quote']);
    }

    public function testReviewApprove()
    {
        $this->assertSubmitForReviewAndReview();
        $this->transitWithForm('Approve', ['oro_workflow_transition[comment]' => 'test_email@test.tst']);
        $this->assertStatuses('reviewed', 'open');
        $this->updateRolePermissionForAction(User::ROLE_ADMINISTRATOR, 'oro_quote_review_and_approve', false);
        $this->assertButtonsAvailable(['Send to Customer']);
    }

    public function testReviewDecline()
    {
        $this->assertSubmitForReviewAndReview();
        $this->transitWithForm('Decline', ['oro_workflow_transition[comment]' => 'test submit comment']);
        $this->assertStatuses('not_approved', 'open');
        $this->updateRolePermissionForAction(User::ROLE_ADMINISTRATOR, 'oro_quote_review_and_approve', false);
        $this->assertButtonsAvailable([]);
    }

    protected function assertSubmitForReviewAndReview()
    {
        $this->updateRolePermissionForAction(User::ROLE_ADMINISTRATOR, 'oro_quote_review_and_approve', false);
        $this->quote = $this->getReference(LoadQuoteData::QUOTE_PRICE_CHANGED);

        $workflowItem = $this->manager->getWorkflowItem($this->quote, static::WORKFLOW_NAME);

        if ($workflowItem) {
            $this->manager->resetWorkflowItem($workflowItem);
        } else {
            $this->manager->startWorkflow(static::WORKFLOW_NAME, $this->quote);
        }
        $this->assertButtonsAvailable(['Edit', 'Clone', 'Delete', 'Submit for Review', 'Send to Customer']);
        $this->transitWithForm('Submit for Review', ['oro_workflow_transition[comment]' => 'test submit comment']);
        $this->assertStatuses('submitted_for_review', 'open');
        $this->assertButtonsAvailable([]);
        $this->updateRolePermissionForAction(User::ROLE_ADMINISTRATOR, 'oro_quote_review_and_approve', true);
        $this->assertButtonsAvailable(['Review']);
        $this->assertBackofficeTransition(
            'Review',
            'under_review',
            'open',
            ['Return', 'Approve and Send to Customer', 'Approve', 'Decline']
        );
    }

    protected function activateWorkflow()
    {
        $this->manager->deactivateWorkflow('b2b_quote_backoffice_default');
        $this->manager->resetWorkflowData('b2b_quote_backoffice_default');

        parent::activateWorkflow();
    }
}
