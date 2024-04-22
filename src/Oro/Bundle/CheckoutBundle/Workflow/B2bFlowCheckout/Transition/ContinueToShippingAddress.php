<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * Implementation of continue_to_shipping_address transition logic of the checkout workflow.
 */
class ContinueToShippingAddress implements TransitionServiceInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private CustomerUserActionsInterface $customerUserActions,
        private AddressActionsInterface $addressActions,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        return true;
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        if (!$this->getCheckout($workflowItem)->getBillingAddress()) {
            return false;
        }

        return true;
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        $checkout = $this->getCheckout($workflowItem);
        $billingAddress = $checkout->getBillingAddress();
        $data = $workflowItem->getData();
        $email = $data['email'];

        $this->customerUserActions->updateGuestCustomerUser($checkout, $email, $billingAddress);
        $this->customerUserActions->createGuestCustomerUser($checkout, $email, $billingAddress);
        $updateAddressResult = $this->addressActions->updateBillingAddress(
            $checkout,
            $data['disallow_shipping_address_edit']
        );
        $data['billing_address_has_shipping'] = $updateAddressResult['billing_address_has_shipping'];

        $this->actionExecutor->executeAction(
            'save_accepted_consents',
            ['acceptedConsents' => $data['customerConsents']]
        );

        if (!$checkout->getCustomerUser()?->isGuest()) {
            $data['customerConsents'] = null;
        }

        if ($data['ship_to_billing_address']) {
            $this->actionExecutor->executeAction(
                'transit_workflow',
                [
                    'entity' => $checkout,
                    'transition' => 'continue_to_shipping_method',
                    'workflow' => $workflowItem->getDefinition()?->getName()
                ]
            );
        }
    }

    private function getCheckout(WorkflowItem $workflowItem): Checkout
    {
        return $workflowItem->getEntity();
    }
}
