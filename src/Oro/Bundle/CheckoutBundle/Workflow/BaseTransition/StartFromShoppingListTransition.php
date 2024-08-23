<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckoutInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\EmptyMatrixGridInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Base implementation of checkout start_from_shopping_list transition.
 */
class StartFromShoppingListTransition extends TransitionServiceAbstract
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private ManagerRegistry $registry,
        private IsWorkflowStartFromShoppingListAllowed $isWorkflowStartFromShoppingListAllowed,
        private StartShoppingListCheckoutInterface $startShoppingListCheckout,
        private ContextAccessorInterface $contextAccessor,
        private EmptyMatrixGridInterface $editableMatrixGrid
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        $shoppingList = $this->getShoppingList($workflowItem);
        if (!$shoppingList) {
            return false;
        }

        if (!$shoppingList->getLineItems() || $shoppingList->getLineItems()->isEmpty()) {
            return false;
        }

        if (!$this->isStartAllowedByListeners($workflowItem, $shoppingList, $errors)) {
            return false;
        }

        if (!$this->isStartAllowed($workflowItem)) {
            return false;
        }

        if (!$this->isAclAllowed($workflowItem, $errors)) {
            return false;
        }

        // Initialize shoppingListHasEmptyMatrix that is used in JS
        // and passed in frontend_options.data.page-component-options
        // Used to show notification "This shopping list contains configurable products with no variations."
        if (!$workflowItem->getResult()->offsetGet('shoppingListHasEmptyMatrix')) {
            $workflowItem->getResult()->offsetSet(
                'shoppingListHasEmptyMatrix',
                $this->editableMatrixGrid->hasEmptyMatrix($shoppingList)
            );
        }

        return true;
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        $result = $this->startShoppingListCheckout->execute($this->getShoppingList($workflowItem), false, true);
        if (!empty($result['workflowItem'])) {
            $workflowItem->merge($result['workflowItem']);
        }

        $workflowItem->getData()->offsetSet('checkout', $result['checkout'] ?? null);
        $workflowItem->getResult()->offsetSet('redirectUrl', $result['redirectUrl'] ?? null);
    }

    private function getShoppingList(WorkflowItem $workflowItem): ?ShoppingList
    {
        $workflowResult = $workflowItem->getResult();
        $shoppingList = $workflowResult->offsetGet('shoppingList');
        if ($shoppingList) {
            return $shoppingList;
        }

        $data = $workflowItem->getData();
        $initContext = $data->offsetGet('init_context');
        if (!$initContext) {
            return null;
        }

        $entityClass = $this->contextAccessor->getValue($initContext, new PropertyPath('entityClass'));
        if (!is_a($entityClass, ShoppingList::class, true)) {
            return null;
        }

        $entityId = $this->contextAccessor->getValue($initContext, new PropertyPath('entityId'));
        $shoppingList = $this->registry
            ->getManagerForClass($entityClass)
            ?->find($entityClass, $entityId);
        $workflowResult->offsetSet('shoppingList', $shoppingList);

        return $shoppingList;
    }

    private function isStartAllowedByListeners(
        WorkflowItem $workflowItem,
        ShoppingList $shoppingList,
        Collection $errors = null
    ): bool {
        $workflowResult = $workflowItem->getResult();
        if (!$workflowResult->offsetExists('extendableConditionShoppingListStart')) {
            $isAllowed = $this->actionExecutor->evaluateExpression(
                expressionName: ExtendableCondition::NAME,
                data: [
                    'events' => ['extendable_condition.shopping_list_start'],
                    'eventData' => [
                        'checkout' => $workflowItem->getEntity(),
                        'shoppingList' => $shoppingList
                    ]
                ],
                errors: $errors
            );
            $workflowResult->offsetSet('extendableConditionShoppingListStart', $isAllowed);
        }

        return $workflowResult->offsetGet('extendableConditionShoppingListStart');
    }

    private function isStartAllowed(WorkflowItem $workflowItem): bool
    {
        $workflowResult = $workflowItem->getResult();
        if (!$workflowResult->offsetExists('isAllowed')) {
            $workflowResult->offsetSet('isAllowed', $this->isWorkflowStartFromShoppingListAllowed->isAllowedForAny());
        }

        return $workflowResult->offsetGet('isAllowed');
    }

    private function isAclAllowed(WorkflowItem $workflowItem, ?Collection $errors): bool
    {
        return $this->actionExecutor->evaluateExpression(
            expressionName: 'acl_granted',
            data: ['CHECKOUT_CREATE', $this->getShoppingList($workflowItem)],
            errors: $errors
        );
    }
}
