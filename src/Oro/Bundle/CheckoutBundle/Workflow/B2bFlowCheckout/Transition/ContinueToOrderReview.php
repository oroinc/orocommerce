<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * B2bCheckout workflow transition continue_to_order_review logic implementation.
 */
class ContinueToOrderReview implements TransitionServiceInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private ShippingMethodActionsInterface $shippingMethodActions,
        private CheckoutPaymentContextProvider $paymentContextProvider,
        private CheckoutActionsInterface $checkoutActions,
        private PaymentMethodActionsInterface $paymentMethodActions,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $this->shippingMethodActions->actualizeShippingMethods(
            $checkout,
            $data->offsetGet('line_items_shipping_methods'),
            $data->offsetGet('line_item_groups_shipping_methods')
        );

        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        if (!$this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors)) {
            return false;
        }

        $paymentContext = $this->getPaymentContext($workflowItem);
        if (!$paymentContext) {
            return false;
        }

        if (!$this->hasApplicablePaymentMethods($paymentContext, $errors)) {
            return false;
        }

        return true;
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        if (!$this->checkRequest('_wid', 'ajax_checkout')) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.invalid_request.message']);

            return false;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        if (!$checkout->getPaymentMethod()) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.payment_method_was_not_selected.message']);

            return false;
        }

        if (!$this->isPaymentMethodApplicable($checkout, $errors)) {
            return false;
        }

        return true;
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        if (!$workflowItem->getData()->offsetGet('payment_validate')) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $workflowResult = $workflowItem->getResult();

        $validateResult = $this->paymentMethodActions->validate(
            checkout: $checkout,
            successUrl: $this->checkoutActions->getCheckoutUrl($checkout),
            failureUrl: $this->checkoutActions->getCheckoutUrl($checkout, 'payment_error'),
            additionalData: $data->offsetGet('additional_data'),
            saveForLaterUse: (bool)$data->offsetGet('payment_save_for_later')
        );

        if ($validateResult) {
            $workflowResult->offsetSet('responseData', $validateResult);
        }
    }

    private function getPaymentContext(WorkflowItem $workflowItem): ?PaymentContextInterface
    {
        $workflowResult = $workflowItem->getResult();
        $paymentContext = $workflowResult->offsetGet('paymentContext');
        if ($paymentContext) {
            return $paymentContext;
        }

        $paymentContext = $this->paymentContextProvider->getContext($workflowItem->getEntity());
        $workflowResult->offsetSet('paymentContext', $paymentContext);

        return $paymentContext;
    }

    private function hasApplicablePaymentMethods(PaymentContextInterface $paymentContext, ?Collection $errors): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'has_applicable_payment_methods',
            [$paymentContext],
            $errors,
            'oro.checkout.workflow.condition.has_applicable_payment_methods.message'
        );
    }

    private function isPaymentMethodApplicable(Checkout $checkout, ?Collection $errors): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'payment_method_applicable',
            [
                'context' => $this->paymentContextProvider->getContext($checkout),
                'payment_method' => $checkout->getPaymentMethod()
            ],
            $errors,
            'oro.checkout.workflow.condition.payment_method_was_not_selected.message'
        );
    }

    private function checkRequest(string $key, string $value): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'check_request',
            [
                'is_ajax' => true,
                'expected_key' => $key,
                'expected_value' => $value
            ]
        );
    }
}
