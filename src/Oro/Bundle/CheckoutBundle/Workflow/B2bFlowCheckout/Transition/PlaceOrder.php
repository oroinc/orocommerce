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
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\PlaceOrder as BasePlaceOrder;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

class PlaceOrder extends BasePlaceOrder
{
    public function __construct(
        ActionExecutor $actionExecutor,
        CheckoutPaymentContextProvider $paymentContextProvider,
        OrderActionsInterface $orderActions,
        CheckoutActionsInterface $checkoutActions,
        TransitionServiceInterface $baseContinueTransition,
        private ConfigProvider $configProvider,
        private SplitOrderActionsInterface $splitOrderActions,
        private ShippingMethodActionsInterface $shippingMethodActions,
    ) {
        parent::__construct(
            $actionExecutor,
            $paymentContextProvider,
            $orderActions,
            $checkoutActions,
            $baseContinueTransition
        );
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

        if (!$this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors)) {
            return false;
        }

        if (!$this->isPaymentMethodApplicable($checkout)) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.payment_method_is_not_applicable.message']);

            return false;
        }

        return parent::isPreConditionAllowed($workflowItem, $errors);
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

    private function placeOrder(Checkout $checkout, ?array $groupedLineItems): Order
    {
        $order = $this->orderActions->placeOrder($checkout);

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
        $purchaseResult = $this->checkoutActions->purchase(
            $checkout,
            $order,
            [
                'failedShippingAddressUrl' => $this->checkoutActions->getCheckoutUrl(
                    $checkout,
                    'back_to_shipping_address_on_fail_address'
                ),
                'additionalData' => $additionalData,
                'email' => $email
            ]
        );

        # Used for cases when sub-orders are paid separately and some of sub-order payments failed.
        if (!empty($purchaseResult['responseData']['purchasePartial'])) {
            $purchaseResult['responseData']['partiallyPaidUrl'] = $this->checkoutActions->getCheckoutUrl(
                $checkout,
                'paid_partially'
            );
        }

        return $purchaseResult['responseData'];
    }
}
