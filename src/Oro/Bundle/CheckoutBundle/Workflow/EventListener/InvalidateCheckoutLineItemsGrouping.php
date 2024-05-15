<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutLineItemGroupingInvalidationHelper;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;

class InvalidateCheckoutLineItemsGrouping
{
    public function __construct(
        private CheckoutWorkflowHelper $checkoutWorkflowHelper,
        private CheckoutLineItemGroupingInvalidationHelper $checkoutLineItemGroupingInvalidationHelper
    ) {
    }

    public function onCheckoutRequest(CheckoutRequestEvent $event): void
    {
        $checkout = $event->getCheckout();
        $workflowItem = $this->checkoutWorkflowHelper->getWorkflowItem($checkout);

        if ($this->checkoutLineItemGroupingInvalidationHelper->shouldInvalidateLineItemGrouping($workflowItem)) {
            $this->checkoutLineItemGroupingInvalidationHelper->invalidateLineItemGrouping($checkout, $workflowItem);
        }
    }
}
