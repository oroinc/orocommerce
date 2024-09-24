<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

/**
 * B2bCheckout workflow transition clear_payment_method_and_recalculate_state logic implementation.
 */
class ClearPaymentMethodAndRecalculateState extends TransitionServiceAbstract
{
    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        return !$checkout->isCompleted();
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();

        $checkout->setShippingCost(null);
        $data->offsetSet('payment_method', null);
        $data->offsetSet('shipping_method', null);
        $data->offsetSet('payment_in_progress', false);
        $data->offsetSet('shipping_data_ready', false);
    }
}
