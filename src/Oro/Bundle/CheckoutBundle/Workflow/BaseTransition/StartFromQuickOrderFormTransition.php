<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Processor\AbstractShoppingListQuickAddProcessor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;

/**
 * Base implementation of checkout start_from_quick_order_form transition.
 */
class StartFromQuickOrderFormTransition extends TransitionServiceAbstract
{
    public function __construct(
        private AbstractShoppingListQuickAddProcessor $quickAddCheckoutProcessor,
        private ShoppingListLimitManager $shoppingListLimitManager,
        private IsWorkflowStartFromShoppingListAllowed $shoppingListAllowed,
        private CurrentShoppingListManager $currentShoppingListManager
    ) {
    }

    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        $isAllowed = false;
        $isCheckoutAllowed = false;

        $resultData = $workflowItem->getResult();
        $this->initializeShowConfirmationAndLimits($resultData);

        if (!$resultData->offsetGet('isAllowed')) {
            $isAllowed = $this->quickAddCheckoutProcessor->isAllowed();
            $resultData->offsetSet('isAllowed', $isAllowed);
        }

        if (!$resultData->offsetGet('isCheckoutAllowed')) {
            $isCheckoutAllowed = $this->shoppingListAllowed->isAllowedForAny();
            $resultData->offsetSet('isCheckoutAllowed', $isCheckoutAllowed);
        }

        return $isAllowed && $isCheckoutAllowed;
    }

    protected function initializeShowConfirmationAndLimits(WorkflowResult $resultData): void
    {
        $isReachedLimit = false;
        if (!$resultData->offsetGet('isReachedLimit')) {
            $isReachedLimit = $this->shoppingListLimitManager->isReachedLimit();
            $resultData->offsetSet('isReachedLimit', $isReachedLimit);
        }

        if (!$resultData->offsetGet('shoppingListLimit')) {
            $resultData->offsetSet('shoppingListLimit', $this->shoppingListLimitManager->getShoppingListLimitForUser());
        }

        $isCurrentShoppingListEmpty = false;
        if (!$resultData->offsetGet('isCurrentShoppingListEmpty')) {
            $isCurrentShoppingListEmpty = $this->currentShoppingListManager->isCurrentShoppingListEmpty();
            $resultData->offsetSet('isCurrentShoppingListEmpty', $isCurrentShoppingListEmpty);
        }

        $resultData->offsetSet('doShowConfirmation', $isReachedLimit && !$isCurrentShoppingListEmpty);
    }
}
