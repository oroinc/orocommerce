<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Implementation of continue_to_shipping_address transition logic of the checkout workflow.
 */
class ContinueToShippingAddress implements TransitionServiceInterface
{
    protected const string CONTINUE_TO_SHIPPING_METHOD_TRANSITION = 'continue_to_shipping_method';

    public function __construct(
        private ActionExecutor $actionExecutor,
        private CustomerUserActionsInterface $customerUserActions,
        private AddressActionsInterface $addressActions,
        private TransitionServiceInterface $baseContinueTransition,
        private WorkflowManager $workflowManager
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
        if (!$this->getCheckout($workflowItem)->getBillingAddress()) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        $checkout = $this->getCheckout($workflowItem);
        $billingAddress = $checkout->getBillingAddress();
        $data = $workflowItem->getData();
        $email = $data->offsetGet('email');

        $this->customerUserActions->updateGuestCustomerUser($checkout, $email, $billingAddress);
        $this->customerUserActions->createGuestCustomerUser($checkout, $email, $billingAddress);
        $data->offsetSet(
            'billing_address_has_shipping',
            $this->addressActions->updateBillingAddress(
                $checkout,
                (bool)$data->offsetGet('disallow_shipping_address_edit')
            )
        );

        $this->actionExecutor->executeAction(
            'save_accepted_consents',
            ['acceptedConsents' => $data->offsetGet('customerConsents')]
        );

        if (!$checkout->getCustomerUser()?->isGuest()) {
            $data->offsetSet('customerConsents', null);
        }

        if ($data->offsetGet('ship_to_billing_address')) {
            $this->workflowManager->transit($workflowItem, static::CONTINUE_TO_SHIPPING_METHOD_TRANSITION);
        }
    }

    private function getCheckout(WorkflowItem $workflowItem): Checkout
    {
        return $workflowItem->getEntity();
    }
}
