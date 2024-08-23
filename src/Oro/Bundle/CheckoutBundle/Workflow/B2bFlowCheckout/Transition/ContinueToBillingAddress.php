<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

/**
 * B2bCheckout workflow transition continue_to_billing_address logic implementation.
 */
class ContinueToBillingAddress extends TransitionServiceAbstract
{
    public function __construct(
        private ActionExecutor $actionExecutor
    ) {
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        $this->actionExecutor->executeAction(
            'save_accepted_consents',
            ['acceptedConsents' => $workflowItem->getData()->offsetGet('customerConsents')]
        );

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        if ($checkout->getCustomerUser() && !$checkout->getCustomerUser()->isGuest()) {
            $workflowItem->getData()->offsetSet('customerConsents', null);
        }
    }
}
