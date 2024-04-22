<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;

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
