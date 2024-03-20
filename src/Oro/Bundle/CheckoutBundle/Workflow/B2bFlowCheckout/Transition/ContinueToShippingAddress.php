<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\AddressActions;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CustomerUserActions;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

class ContinueToShippingAddress implements TransitionServiceInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private ConfigProvider $multiShippingConfigProvider,
        private GroupedCheckoutLineItemsProvider $checkoutLineItemsProvider,
        private CustomerUserActions $customerUserActions,
        private AddressActions $addressActions
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        $checkout = $this->getCheckout($workflowItem);
        if ($checkout->isCompleted()) {
            return false;
        }
        $this->initializeGroupedLineItems($workflowItem, $checkout);

        $data = $this->actionExecutor->executeActionGroup(
            'order_line_items_not_empty',
            ['checkout' => $checkout]
        );

        if (!$data['orderLineItemsNotEmptyForRfp']) {
            $errors?->add(
                ['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.not_allow_rfp.message']
            );

            return false;
        }

        if (!$data['orderLineItemsNotEmpty']) {
            $errors?->add(
                ['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.allow_rfp.message']
            );

            return false;
        }

        $quoteAcceptable = $this->actionExecutor->evaluateExpression(
            'quote_acceptable',
            [$checkout->getSourceEntity(), true],
            $errors
        );
        if (!$quoteAcceptable) {
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

    // TODO: Move me to start transition or event reacting on workflow start, this MUST BE NOT executed in preaction
    private function initializeGroupedLineItems(WorkflowItem $workflowItem, Checkout $checkout): void
    {
        if (!$this->multiShippingConfigProvider->isLineItemsGroupingEnabled()) {
            return;
        }

        $workflowItem->getData()->set(
            'grouped_line_items',
            $this->checkoutLineItemsProvider->getGroupedLineItemsIds($checkout)
        );
    }

    private function getCheckout(WorkflowItem $workflowItem): Checkout
    {
        return $workflowItem->getEntity();
    }
}
