<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * Base implementation of checkout place_order transition.
 */
abstract class PlaceOrder implements TransitionServiceInterface
{
    use ValidationTrait;

    public function __construct(
        protected ActionExecutor $actionExecutor,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        if (!$workflowItem->getId()) {
            return false;
        }

        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        return $this->isValidationPassed($workflowItem->getEntity(), 'checkout_order_create_pre_checks', $errors);
    }

    #[\Override]
    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        return $this->isValidationPassed($workflowItem->getEntity(), 'checkout_order_create_checks', $errors);
    }

    protected function showPaymentInProgressNotification(Checkout $checkout, bool $paymentInProgress): void
    {
        if ($paymentInProgress && !$checkout->isCompleted()) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.checkout.workflow.condition.payment_has_not_been_processed.message',
                    'type' => 'warning'
                ]
            );
        }
    }
}
