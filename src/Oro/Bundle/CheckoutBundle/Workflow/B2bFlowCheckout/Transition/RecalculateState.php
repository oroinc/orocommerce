<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

/**
 * B2bCheckout workflow transition recalculate_state logic implementation.
 */
class RecalculateState extends TransitionServiceAbstract
{
    public function __construct(
        private UpdateShippingPriceInterface $updateShippingPrice
    ) {
    }

    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, ?Collection $errors = null): bool
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

        $this->updateShippingPrice->execute($checkout);
        $workflowItem->getResult()->offsetSet('shippingPriceUpdated', true);

        $workflowItem->getData()->offsetSet('payment_in_progress', false);
    }
}
