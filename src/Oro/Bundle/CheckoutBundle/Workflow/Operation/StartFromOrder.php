<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\Operation;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\AbstractOperationService;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartCheckoutInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * oro_checkout_frontend_start_from_order operation logic implementation
 */
class StartFromOrder extends AbstractOperationService
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private CheckoutLineItemsFactory $lineItemsFactory,
        private StartCheckoutInterface $startCheckout,
        private CheckoutLineItemsProvider $checkoutLineItemsProvider,
        private ActionExecutor $actionExecutor,
        private OrderLimitProviderInterface $orderLimitProvider,
        private OrderLimitFormattedProviderInterface $orderLimitFormattedProvider
    ) {
    }

    public function isPreConditionAllowed(ActionData $data, ?Collection $errors = null): bool
    {
        $applicableWorkflow = $this->workflowManager
            ->getAvailableWorkflowByRecordGroup(Checkout::class, 'b2b_checkout_flow');

        return null !== $applicableWorkflow;
    }

    public function execute(ActionData $data): void
    {
        $order = $data->getEntity();
        if (!$order instanceof Order) {
            throw new WorkflowException('Only Order entity is supported');
        }

        if (!$this->assertOrderLineItems($order) || !$this->assertOrderLimits($order)) {
            return;
        }

        $checkout = $this->startCheckout($order, $data);

        $this->checkChangedSkus($checkout, $order);
    }

    private function assertOrderLineItems(Order $order): bool
    {
        $checkoutLineItems = $this->lineItemsFactory->create($order);
        if ($checkoutLineItems->isEmpty()) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.checkout.frontend.checkout.cannot_create_reorder_no_line_items',
                    'type' => 'warning'
                ]
            );

            return false;
        }

        return true;
    }

    private function assertOrderLimits(Order $order): bool
    {
        if (!$this->orderLimitProvider->isMinimumOrderAmountMet($order)) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_flash',
                    'message_parameters' => [
                        'amount' => $this->orderLimitFormattedProvider->getMinimumOrderAmountFormatted(),
                        'difference' =>
                            $this->orderLimitFormattedProvider->getMinimumOrderAmountDifferenceFormatted($order),
                    ],
                    'type' => 'error'
                ]
            );

            return false;
        }

        if (!$this->orderLimitProvider->isMaximumOrderAmountMet($order)) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_flash',
                    'message_parameters' => [
                        'amount' => $this->orderLimitFormattedProvider->getMaximumOrderAmountFormatted(),
                        'difference' =>
                            $this->orderLimitFormattedProvider->getMaximumOrderAmountDifferenceFormatted($order),
                    ],
                    'type' => 'error'
                ]
            );

            return false;
        }

        return true;
    }

    private function startCheckout(Order $order, ActionData $data): Checkout
    {
        $startResult = $this->startCheckout->execute(
            sourceCriteria: ['order' => $order],
            force: true,
            settings: [
                'allow_manual_source_remove' => false,
                'remove_source' => false
            ],
            showErrors: true,
            forceStartCheckout: true,
            validateOnStartCheckout: true
        );

        $checkout = $startResult['checkout'];
        $data->offsetSet('checkout', $checkout);

        $data->offsetSet('errors', $startResult['errors'] ?? []);
        $data->offsetSet('redirectUrl', $startResult['redirectUrl'] ?? null);

        return $checkout;
    }

    private function checkChangedSkus(mixed $checkout, Order $order): void
    {
        $changedSkus = $this->checkoutLineItemsProvider->getProductSkusWithDifferences(
            $checkout->getLineItems(),
            $order->getLineItems()
        );
        if (count($changedSkus) > 0) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.checkout.frontend.checkout.some_changes_in_line_items',
                    'message_parameters' => [
                        'skus' => implode(', ', $changedSkus)
                    ],
                    'type' => 'warning'
                ]
            );
        }
    }
}
