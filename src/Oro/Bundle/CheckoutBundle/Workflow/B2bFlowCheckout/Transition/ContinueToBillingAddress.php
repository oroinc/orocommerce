<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
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

    #[\Override]
    public function isConditionAllowed(WorkflowItem $workflowItem, ?Collection $errors = null): bool
    {
        if (!parent::isConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        if (!$this->isEmailConfirmed($workflowItem, $errors)) {
            return false;
        }

        return true;
    }

    #[\Override]
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

    private function isEmailConfirmed(WorkflowItem $workflowItem, ?Collection $errors = null): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'is_email_confirmed',
            ['checkout' => $workflowItem->getEntity()],
            $errors
        );
    }
}
