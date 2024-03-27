<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;

/**
 * Process workflow and sets current step to the event.
 */
class ProcessWorkflowRequestEventListener
{
    public function __construct(
        private CheckoutWorkflowHelper $checkoutWorkflowHelper
    ) {
    }

    public function onCheckoutRequest(CheckoutRequestEvent $event): void
    {
        $workflowStep = $this->checkoutWorkflowHelper->processWorkflowAndGetCurrentStep(
            $event->getRequest(),
            $event->getCheckout()
        );

        $event->setWorkflowStep($workflowStep);
    }
}
