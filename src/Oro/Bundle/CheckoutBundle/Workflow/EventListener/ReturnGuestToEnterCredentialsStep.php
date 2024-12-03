<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionCompletedEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Returns guest customer users to the first checkout step when entering checkout from source entities.
 */
class ReturnGuestToEnterCredentialsStep
{
    public function __construct(
        private WorkflowManager $workflowManager
    ) {
    }

    public function onComplete(TransitionCompletedEvent $event): void
    {
        $backToLoginTransition = $this->getBackTransitionName($event);
        if (!$backToLoginTransition) {
            return;
        }

        if (!$event->getTransition()->isStart()) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        // No step yet, no need to transit
        if (!$workflowItem->getCurrentStep()) {
            return;
        }

        $workflow = $this->workflowManager->getWorkflow($workflowItem);
        $backTransition = $workflow->getTransitionManager()->getTransition($backToLoginTransition);
        if (!$backTransition) {
            return;
        }

        $loginStepName = $backTransition->getStepTo()->getName();

        $currentStepName = $workflowItem->getCurrentStep()->getName();
        // The current step is already enter_credentials, nothing to do
        if ($currentStepName === $loginStepName) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        // Whe should redirect only guests, skip for registered users
        if ($checkout->getCustomerUser() && !$checkout->getCustomerUser()->isGuest()) {
            return;
        }

        $stepManager = $workflow->getStepManager();
        // back_to_enter_credentials is not allowed for the current step
        if (!$stepManager->getStep($currentStepName)->isAllowedTransition($backToLoginTransition)) {
            return;
        }

        $this->workflowManager->transit($workflowItem, $backTransition);
    }

    private function getBackTransitionName(WorkflowItemAwareEvent $event): ?string
    {
        $workflowMetadata = $event->getWorkflowItem()->getDefinition()?->getMetadata() ?? [];

        return $workflowMetadata['guest_checkout']['return_to_login_transition'] ?? null;
    }
}
