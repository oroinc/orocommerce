<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Handler\CheckoutHandlerInterface;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Process workflow and sets the current step to the event.
 */
class ProcessWorkflowRequestEventListener
{
    public function __construct(
        private CheckoutWorkflowHelper $checkoutWorkflowHelper,
        private WorkflowManager $workflowManager,
        private CheckoutHandlerInterface $checkoutHandler
    ) {
    }

    public function onCheckoutRequest(CheckoutRequestEvent $event): void
    {
        $request = $event->getRequest();
        $checkout = $event->getCheckout();
        $workflowItem = $this->checkoutWorkflowHelper->getWorkflowItem($checkout);

        $this->checkoutHandler->handle($workflowItem, $request);
        $workflowStep = $this->validateAndGetCurrentStep($workflowItem);

        $event->setWorkflowStep($workflowStep);
    }

    private function validateAndGetCurrentStep(WorkflowItem $workflowItem): WorkflowStep
    {
        $verifyTransition = null;
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
        foreach ($transitions as $transition) {
            $frontendOptions = $transition->getFrontendOptions();
            if (!empty($frontendOptions['is_checkout_verify'])) {
                $verifyTransition = $transition;
                break;
            }
        }

        if ($verifyTransition) {
            $this->workflowManager->transitIfAllowed($workflowItem, $verifyTransition);
        }

        return $workflowItem->getCurrentStep();
    }
}
