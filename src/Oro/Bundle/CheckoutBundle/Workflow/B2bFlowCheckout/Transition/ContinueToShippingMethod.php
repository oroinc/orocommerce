<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * Implementation of continue_to_shipping_method transition logic of the checkout workflow.
 */
class ContinueToShippingMethod implements TransitionServiceInterface
{
    public function __construct(
        private AddressActionsInterface $addressActions,
        private ShippingMethodActionsInterface $shippingMethodActions,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        if ($checkout->isShipToBillingAddress()) {
            return true;
        }

        if ($checkout->getShippingAddress()) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $this->addressActions->updateShippingAddress($checkout);
        $this->updateEmail($workflowItem, $checkout);
        $this->shippingMethodActions->updateDefaultShippingMethods($checkout, null, null, false);
    }

    private function updateEmail(WorkflowItem $workflowItem, Checkout $checkout): void
    {
        if (!$checkout->getBillingAddress()?->getCustomerUserAddress()) {
            return;
        }

        $workflowItem->getData()->set('email', $checkout->getCustomerUser()?->getEmail());
    }
}
