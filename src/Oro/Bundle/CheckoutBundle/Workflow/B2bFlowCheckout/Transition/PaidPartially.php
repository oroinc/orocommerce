<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaidPartially extends TransitionServiceAbstract
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private UrlGeneratorInterface $urlGenerator,
        private GroupedCheckoutLineItemsProvider $groupedCheckoutLineItemsProvider
    ) {
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $order = $data->offsetGet('order');
        if ($order instanceof Order) {
            $orderViewPageUrl = $this->urlGenerator->generate('oro_order_frontend_view', ['id' => $order->getId()]);
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.checkout.workflow.condition.payment_has_not_fully_been_processed.message',
                    'message_parameters' => [
                        'orderIdentifier' => $order->getIdentifier(),
                        'orderViewLink' => $orderViewPageUrl
                    ],
                    'type' => 'warning'
                ]
            );
            $this->actionExecutor->executeAction(
                'actualize_line_items_by_unpaid_suborders',
                [
                    'order' => $order,
                    'checkout' => $checkout
                ]
            );
            $data->offsetSet(
                'grouped_line_items',
                $this->groupedCheckoutLineItemsProvider->getGroupedLineItemsIds($checkout)
            );
        }

        $data->offsetSet('payment_method', null);
        $data->offsetSet('payment_in_progress', false);
        $data->offsetUnset('order');
    }
}
