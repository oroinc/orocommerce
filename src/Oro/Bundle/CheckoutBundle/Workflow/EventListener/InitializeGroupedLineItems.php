<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;

/**
 * Initializes grouped_line_items when entering multistep checkout workflow with start transition.
 */
class InitializeGroupedLineItems
{
    public function __construct(
        private ConfigProvider $multiShippingConfigProvider,
        private GroupedCheckoutLineItemsProvider $checkoutLineItemsProvider
    ) {
    }

    public function onComplete(TransitionEvent $event): void
    {
        if (!$event->getTransition()->isStart()) {
            return;
        }

        if (!CheckoutWorkflowHelper::isMultiStepCheckoutWorkflow($event->getWorkflowItem())) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        $checkout = $workflowItem->getEntity();
        if (!$checkout instanceof Checkout) {
            return;
        }

        if (!$this->multiShippingConfigProvider->isLineItemsGroupingEnabled()) {
            return;
        }

        $workflowItem->getData()->set(
            'grouped_line_items',
            $this->checkoutLineItemsProvider->getGroupedLineItemsIds($checkout)
        );
    }
}
