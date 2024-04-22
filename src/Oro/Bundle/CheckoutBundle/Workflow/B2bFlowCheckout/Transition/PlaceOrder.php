<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\OrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\SplitOrderActionsInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PlaceOrder implements TransitionServiceInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private ShippingMethodActionsInterface $shippingMethodActions,
        private CheckoutPaymentContextProvider $paymentContextProvider,
        private UrlGeneratorInterface $urlGenerator,
        private OrderActionsInterface $orderActions,
        private SplitOrderActionsInterface $splitOrderActions,
        private CheckoutActionsInterface $checkoutActions,
        private ConfigProvider $configProvider,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();

        $this->showPaymentInProgressNotification($checkout, (bool)$data->offsetGet('payment_in_progress'));

        $this->shippingMethodActions->actualizeShippingMethods(
            $checkout,
            $data->offsetGet('line_items_shipping_methods'),
            $data->offsetGet('line_item_groups_shipping_methods')
        );

        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        if (!$this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors)['hasRules']) {
            return false;
        }

        if (!$workflowItem->getId()) {
            return false;
        }

        if (!$this->isPaymentMethodApplicable($checkout)) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.payment_method_is_not_applicable.message']);

            return false;
        }

        if (!$this->isPreOrderCreateAllowedByEventListeners($workflowItem, $errors)) {
            return false;
        }

        return true;
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        return $this->actionExecutor->evaluateExpression(
            expressionName: 'extendable',
            data: ['events' => ['extendable_condition.before_order_create']],
            errors: $errors,
            message: 'oro.checkout.workflow.b2b_flow_checkout.transition.place_order.condition.extendable.message',
            context: $workflowItem
        );
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $workflowResult = $workflowItem->getResult();
        $data = $workflowItem->getData();

        $order = $this->placeOrder($checkout, $data->offsetGet('grouped_line_items'));
        $data->offsetSet('order', $order);

        $data->offsetSet('payment_in_progress', true);
        $responseData = $this->executeCheckoutPurchase(
            $checkout,
            $order,
            $data->offsetGet('additional_data'),
            $data->offsetGet('email')
        );
        $responseData = array_merge((array)$workflowResult->offsetGet('responseData'), $responseData);
        $workflowResult->offsetSet('responseData', $responseData);

        $this->actionExecutor->executeAction(
            'extendable',
            ['events' => ['extendable_action.finish_checkout']],
            $workflowItem
        );

        if (empty($responseData['paymentMethodSupportsValidation'])) {
            return;
        }

        $url = !empty($responseData['purchaseSuccessful']) ? $responseData['successUrl'] : $responseData['failureUrl'];
        $workflowItem->getResult()->offsetSet('redirectUrl', $url);
    }

    private function showPaymentInProgressNotification(Checkout $checkout, bool $paymentInProgress): void
    {
        if ($paymentInProgress && !$checkout->isCompleted()) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.checkout.workflow.condition.payment_has_not_been_processed.message',
                    'type' => 'warning'
                ]
            );
        }
    }

    private function isPaymentMethodApplicable(Checkout $checkout): bool
    {
        $paymentContext = $this->paymentContextProvider->getContext($checkout);
        if (!$paymentContext) {
            return false;
        }

        return $this->actionExecutor->evaluateExpression(
            'payment_method_applicable',
            [
                'context' => $paymentContext,
                'payment_method' => $checkout->getPaymentMethod()
            ]
        );
    }

    private function isPreOrderCreateAllowedByEventListeners(WorkflowItem $workflowItem, ?Collection $errors): bool
    {
        $workflowResult = $workflowItem->getResult();
        $savedInResult = $workflowResult->offsetGet('extendableConditionPreOrderCreate');
        if ($savedInResult !== null) {
            return $savedInResult;
        }

        $isAllowed = $this->actionExecutor->evaluateExpression(
            expressionName: 'extendable',
            data: ['events' => ['extendable_condition.pre_order_create']],
            errors: $errors,
            context: $workflowItem
        );
        $workflowResult->offsetSet('extendableConditionPreOrderCreate', $isAllowed);

        return $isAllowed;
    }

    private function placeOrder(Checkout $checkout, ?array $groupedLineItems): Order
    {
        $placeOrderResult = $this->orderActions->placeOrder($checkout);
        $order = $placeOrderResult['order'];

        if ($groupedLineItems && $this->configProvider->isCreateSubOrdersForEachGroupEnabled()) {
            $this->splitOrderActions->createChildOrders($checkout, $order, $groupedLineItems);
        }

        return $order;
    }

    private function executeCheckoutPurchase(
        Checkout $checkout,
        Order $order,
        ?string $additionalData,
        ?string $email
    ): array {
        $failedShippingAddressUrl = $this->urlGenerator
            ->generate(
                'oro_checkout_frontend_checkout',
                [
                    'id' => $checkout->getId(),
                    'transition' => 'back_to_shipping_address_on_fail_address'
                ]
            );
        $purchaseResult = $this->checkoutActions->purchase(
            $checkout,
            $order,
            [
                'failedShippingAddressUrl' => $failedShippingAddressUrl,
                'additionalData' => $additionalData,
                'email' => $email
            ]
        );

        # Used for cases when sub-orders are paid separately and some of sub-order payments failed.
        if (!empty($purchaseResult['responseData']['purchasePartial'])) {
            $purchaseResult['responseData']['partiallyPaidUrl'] = $this->urlGenerator->generate(
                'oro_checkout_frontend_checkout',
                [
                    'id' => $checkout->getId(),
                    'transition' => 'paid_partially'
                ]
            );
        }

        return $purchaseResult['responseData'];
    }
}
