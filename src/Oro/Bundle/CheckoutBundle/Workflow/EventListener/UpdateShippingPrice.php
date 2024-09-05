<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;

/**
 * Update shipping price on form init for the checkout continue transitions when shippingMethod is selected.
 */
class UpdateShippingPrice
{
    public function __construct(
        private UpdateShippingPriceInterface $updateShippingPrice
    ) {
    }

    public function updateShippingPrice(TransitionEvent $event): void
    {
        $workflowItem = $event->getWorkflowItem();
        if (!CheckoutWorkflowHelper::isCheckoutWorkflow($workflowItem)) {
            return;
        }

        $transition = $event->getTransition();
        if (empty($transition->getFrontendOptions()['is_checkout_continue'])) {
            return;
        }

        // Shipping price was updated already
        if ($workflowItem->getResult()->offsetGet('shippingPriceUpdated')) {
            return;
        }

        // Allow automatic recalculation when all shipping data is prepared and ready
        if (!$workflowItem->getData()->offsetGet('shipping_data_ready')) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $event->getWorkflowItem()->getEntity();
        if (!$checkout->getShippingMethod()) {
            return;
        }

        $this->updateShippingPrice->execute($checkout);
        $workflowItem->getResult()->offsetSet('shippingPriceUpdated', true);
    }
}
