<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class ReturnGuestToEnterCredentialsStep
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private string $stepName = 'enter_credentials_step',
        private string $transitionName = 'back_to_enter_credentials'
    ) {
    }

    public function onComplete(TransitionEvent $event): void
    {
        if (!$event->getTransition()->isStart()) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        // No step yet, no need to transit
        if (!$workflowItem->getCurrentStep()) {
            return;
        }

        $currentStepName = $workflowItem->getCurrentStep()->getName();
        // There is no enter_credentials in this workflow, nothing to do
        if (!$workflowItem->getDefinition()?->getStepByName($this->stepName)) {
            return;
        }
        // The current step is already enter_credentials, nothing to do
        if ($currentStepName === $this->stepName) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        // Whe should redirect only guests, skip for registered users
        if ($checkout->getCustomerUser() && !$checkout->getCustomerUser()->isGuest()) {
            return;
        }

        $stepManager = $this->workflowManager->getWorkflow($workflowItem)->getStepManager();
        // back_to_enter_credentials is not allowed for the current step
        if (!$stepManager->getStep($currentStepName)->isAllowedTransition($this->transitionName)) {
            return;
        }

        $this->workflowManager->transit($workflowItem, $this->transitionName);
    }
}
