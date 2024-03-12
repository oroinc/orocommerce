<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

class ContinueToShippingAddress implements TransitionServiceInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private ConfigProvider $multiShippingConfigProvider,
        private GroupedCheckoutLineItemsProvider $checkoutLineItemsProvider
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
            $errors->add(
                ['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.not_allow_rfp.message']
            );

            return false;
        }

        if (!$data['orderLineItemsNotEmpty']) {
            $errors->add(
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
        if (!$this->getCheckoutStateValid($workflowItem, $errors)) {
            return false;
        }

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

        $this->actionExecutor->executeActionGroup(
            'b2b_flow_checkout_update_guest_customer_user',
            [
                'checkout' => $checkout,
                'email' => $data->get('email'),
                'billing_address' => $billingAddress
            ]
        );

        $this->actionExecutor->executeActionGroup(
            'b2b_flow_checkout_create_guest_customer_user',
            [
                'checkout' => $checkout,
                'email' => $data->get('email'),
                'billing_address' => $billingAddress
            ]
        );

        $updateAddressResult = $this->actionExecutor->executeActionGroup(
            'b2b_flow_checkout_update_billing_address',
            [
                'checkout' => $checkout,
                'disallow_shipping_address_edit' => $data->get('disallow_shipping_address_edit')
            ]
        );
        $data->set(
            'billing_address_has_shipping',
            $updateAddressResult->get('billing_address_has_shipping')
        );

        $this->actionExecutor->executeAction(
            'save_accepted_consents',
            ['acceptedConsents' => $data->get('customerConsents')]
        );

        if ($checkout->getCustomerUser()?->isGuest()) {
            $data->set('customerConsents', null);
        }

        $data->set('state_token', UUIDGenerator::v4());

        if ($data->get('ship_to_billing_address')) {
            $this->actionExecutor->executeAction(
                'transit_workflow',
                [
                    'entity' => $checkout,
                    'transition' => 'continue_to_shipping_method',
                    'workflow' => $workflowItem->getDefinition()->getName()
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

    // TODO: Move me to EVENT for continue checkout transitions.
    private function getCheckoutStateValid(WorkflowItem $workflowItem, ?Collection $errors): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'is_checkout_state_valid',
            [
                'entity' => $this->getCheckout($workflowItem),
                'token' => $workflowItem->getData()->get('state_token'),
                'current_state' => $workflowItem->getResult()->get('currentCheckoutState')
            ],
            $errors,
            'oro.checkout.workflow.condition.content_of_order_was_changed.message'
        );
    }
}
