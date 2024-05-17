<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Component\Action\Action\ExtendableAction;
use Oro\Component\Action\Condition\ExtendableCondition;

class Purchase extends TransitionServiceAbstract
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private CheckoutActionsInterface $checkoutActions,
        private ShippingMethodActionsInterface $shippingMethodActions,
        private PaymentMethodActionsInterface $paymentMethodActions,
        private CheckoutPaymentContextProvider $paymentContextProvider,
        private PaymentTransactionProvider $paymentTransactionProvider,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();

        if ($checkout->isCompleted()) {
            return false;
        }

        if (!$data->offsetGet('order')) {
            return false;
        }

        if ($this->paymentMethodActions->isPaymentMethodSupportsValidate($checkout)
            && !$this->paymentTransactionProvider->getActiveValidatePaymentTransaction($checkout->getPaymentMethod())
        ) {
            return false;
        }

        if ($this->isPurchaseViaDirectUrl()) {
            if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
                return false;
            }

            if (!$this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors)) {
                return false;
            }

            $paymentContext = $this->paymentContextProvider->getContext($checkout);
            if (!$this->hasApplicablePaymentMethods($paymentContext, $errors)) {
                return false;
            }

            if (!$this->isOrderCreateAllowedByEventListeners($workflowItem, $errors)) {
                return false;
            }
        }

        return true;
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $workflowResult = $workflowItem->getResult();
        $order = $data->offsetGet('order');

        $data->offsetSet('payment_in_progress', true);

        $purchaseResult = $this->checkoutActions->purchase(
            $checkout,
            $order,
            [
                'additionalData' => $data->offsetGet('additional_data'),
                'email' => $data->offsetGet('email')
            ]
        );
        $responseData = array_merge((array)$workflowResult->offsetGet('responseData'), $purchaseResult['responseData']);
        $workflowResult->offsetSet('responseData', $responseData);

        $this->actionExecutor->executeAction(
            ExtendableAction::NAME,
            [
                'events' => ['extendable_action.finish_checkout'],
                'eventData' => [
                    'order' => $order,
                    'checkout' => $checkout,
                    'responseData' => $responseData,
                    'email' => $data->offsetGet('email')
                ]
            ]
        );

        if (!empty($responseData['purchaseSuccessful'])) {
            $workflowItem->setRedirectUrl($this->checkoutActions->getCheckoutUrl($checkout, 'finish_checkout'));
        } else {
            $workflowItem->setRedirectUrl($this->checkoutActions->getCheckoutUrl($checkout, 'payment_error'));
        }
    }

    private function isPurchaseViaDirectUrl(): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'check_request',
            [
                'expected_key' => 'transition',
                'expected_value' => 'purchase'
            ]
        );
    }

    private function hasApplicablePaymentMethods(?PaymentContextInterface $paymentContext, ?Collection $errors): bool
    {
        if (!$paymentContext) {
            return false;
        }

        return $this->actionExecutor->evaluateExpression(
            expressionName: 'has_applicable_payment_methods',
            data: [$paymentContext],
            errors: $errors,
            message: 'oro.checkout.workflow.condition.payment_method_is_not_applicable.message'
        );
    }

    private function isOrderCreateAllowedByEventListeners(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        $data = $workflowItem->getData();

        return $this->actionExecutor->evaluateExpression(
            expressionName: ExtendableCondition::NAME,
            data: [
                'events' => ['extendable_condition.before_order_create'],
                'eventData' => [
                    'checkout' => $data->offsetGet('checkout'),
                    'order' => $data->offsetGet('order')
                ]
            ],
            errors: $errors,
            message: 'oro.checkout.workflow.b2b_flow_checkout.transition.place_order.condition.extendable.message'
        );
    }
}
