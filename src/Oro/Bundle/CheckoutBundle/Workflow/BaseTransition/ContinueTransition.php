<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderLineItemsNotEmptyInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

/**
 * Base implementation of B2bCheckout workflow continue transition (transition that leads to the next step).
 */
class ContinueTransition extends TransitionServiceAbstract
{
    public function __construct(
        protected ActionExecutor $actionExecutor,
        protected OrderLineItemsNotEmptyInterface $orderLineItemsNotEmpty
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        if ($checkout->isCompleted()) {
            return false;
        }

        if (!$this->checkOrderLineItems($checkout, $errors)) {
            return false;
        }

        $quoteAcceptable = $this->actionExecutor->evaluateExpression(
            'quote_acceptable',
            [$checkout->getSourceEntity(), true],
            $errors
        );
        if (!$quoteAcceptable) {
            return false;
        }

        return true;
    }

    protected function checkOrderLineItems(Checkout $checkout, ?Collection $errors = null): bool
    {
        $orderLineItemsNotEmptyResult = $this->orderLineItemsNotEmpty->execute($checkout);
        if (empty($orderLineItemsNotEmptyResult['orderLineItemsNotEmptyForRfp'])) {
            $errors?->add(
                ['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.not_allow_rfp.message']
            );

            return false;
        }

        if (empty($orderLineItemsNotEmptyResult['orderLineItemsNotEmpty'])) {
            $errors?->add(
                ['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.allow_rfp.message']
            );

            return false;
        }

        return true;
    }
}
