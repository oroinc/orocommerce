<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderActionsInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\Action\Event\ExtendableConditionEvent;

/**
 * Base implementation of checkout place_order transition.
 */
abstract class PlaceOrder implements TransitionServiceInterface
{
    public function __construct(
        protected ActionExecutor $actionExecutor,
        protected CheckoutPaymentContextProvider $paymentContextProvider,
        protected OrderActionsInterface $orderActions,
        protected CheckoutActionsInterface $checkoutActions,
        protected TransitionServiceInterface $baseContinueTransition
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        if (!$workflowItem->getId()) {
            return false;
        }

        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        if (!$this->isPreOrderCreateAllowedByEventListeners($workflowItem, $errors)) {
            return false;
        }

        return true;
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        return $this->actionExecutor->evaluateExpression(
            expressionName: ExtendableCondition::NAME,
            data: [
                'events' => ['extendable_condition.before_order_create'],
                'eventData' => [
                    'checkout' => $workflowItem->getEntity(),
                    ExtendableConditionEvent::CONTEXT_KEY => $workflowItem
                ]
            ],
            errors: $errors,
            message: 'oro.checkout.workflow.b2b_flow_checkout.transition.place_order.condition.extendable.message'
        );
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

    protected function isPaymentMethodApplicable(Checkout $checkout): bool
    {
        $paymentContext = $this->paymentContextProvider->getContext($checkout);
        if (!$paymentContext) {
            return false;
        }

        return $this->actionExecutor->evaluateExpression(
            'payment_method_applicable',
            [
                'context' => $paymentContext,
                'payment_method' => $checkout->getPaymentMethod()
            ]
        );
    }

    protected function isPreOrderCreateAllowedByEventListeners(WorkflowItem $workflowItem, ?Collection $errors): bool
    {
        $workflowResult = $workflowItem->getResult();
        $savedInResult = $workflowResult->offsetGet('extendableConditionPreOrderCreate');
        if ($savedInResult !== null) {
            return $savedInResult;
        }

        $isAllowed = $this->actionExecutor->evaluateExpression(
            expressionName: ExtendableCondition::NAME,
            data: [
                'events' => ['extendable_condition.pre_order_create'],
                'eventData' => [
                    'checkout' => $workflowItem->getEntity(),
                    ExtendableConditionEvent::CONTEXT_KEY => $workflowItem
                ]
            ],
            errors: $errors
        );
        $workflowResult->offsetSet('extendableConditionPreOrderCreate', $isAllowed);

        return $isAllowed;
    }
}
