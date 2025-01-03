<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\SplitOrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\PlaceOrder as BasePlaceOrder;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Component\Action\Action\ExtendableAction;

/**
 * B2bCheckout workflow transition place_order logic implementation.
 */
class PlaceOrder extends BasePlaceOrder
{
    private ?CheckoutLineItemsConverter $checkoutLineItemsConverter = null;

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

    public function setCheckoutLineItemsConverter(CheckoutLineItemsConverter $checkoutLineItemsConverter): void
    {
        $this->checkoutLineItemsConverter = $checkoutLineItemsConverter;
    }

    #[\Override]
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

    #[\Override]
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

        if (empty($responseData['paymentMethodSupportsValidation'])) {
            return;
        }

        $url = !empty($responseData['purchaseSuccessful']) ? $responseData['successUrl'] : $responseData['failureUrl'];
        $workflowItem->getResult()->offsetSet('redirectUrl', $url);
    }

    private function placeOrder(Checkout $checkout, ?array $groupedLineItems): Order
    {
        if ($groupedLineItems && $this->configProvider->isCreateSubOrdersForEachGroupEnabled()) {
            $this->checkoutLineItemsConverter->setReuseLineItems(true);
        }

        $order = $this->orderActions->placeOrder($checkout);

        try {
            if ($groupedLineItems && $this->configProvider->isCreateSubOrdersForEachGroupEnabled()) {
                $this->splitOrderActions->createChildOrders($checkout, $order, $groupedLineItems);
            }
        } finally {
            $this->checkoutLineItemsConverter->setReuseLineItems(false);
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
