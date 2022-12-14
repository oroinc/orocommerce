<?php

namespace Oro\Bundle\CheckoutBundle\Helper;

use Exception;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShoppingListLineItemDiffMapper;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * To compare two checkout line item collections if any differences existed.
 */
class CheckoutCompareHelper
{
    private CheckoutDiffStorageInterface $diffStorage;

    private ShoppingListLineItemDiffMapper $diffMapper;

    private WorkflowManager $workflowManager;

    private ActionGroupRegistry $actionGroupRegistry;

    public function __construct(
        CheckoutDiffStorageInterface $diffStorage,
        ShoppingListLineItemDiffMapper $diffMapper,
        WorkflowManager $workflowManager,
        ActionGroupRegistry $actionGroupRegistry
    ) {
        $this->diffStorage = $diffStorage;
        $this->diffMapper = $diffMapper;
        $this->workflowManager = $workflowManager;
        $this->actionGroupRegistry = $actionGroupRegistry;
    }

    public function compare(CheckoutInterface $checkout)
    {
        $shoppingList = $checkout->getSourceEntity();
        if ($shoppingList instanceof ShoppingList) {
            $this->startShoppingListCheckoutAction($shoppingList);
        }
    }

    /**
     * @throws WorkflowException
     */
    public function resetCheckoutIfSourceLineItemsChanged(?Checkout $checkout, ?Checkout $rawCheckout): ?Checkout
    {
        if ($checkout !== null && $rawCheckout !== null) {
            /** @var WorkflowItem $workflowItem */
            $items = $this->workflowManager->getWorkflowItemsByEntity($checkout);

            if (count($items) !== 1) {
                throw new NotFoundHttpException('Unable to find correct WorkflowItem for current checkout');
            }

            $workflowItem = reset($items);
            $workflowData = $workflowItem->getData();

            if ($workflowData->has('state_token')) {
                $stateToken = $workflowData->get('state_token');

                $diffKey = $this->diffMapper->getName();
                $state1 = $this->diffStorage->getState($checkout, $stateToken);
                $state1 = array_key_exists($diffKey, $state1) ? $state1[$diffKey] : [];
                $state2 = $this->diffMapper->getCurrentState($rawCheckout);

                if (!$this->diffMapper->isStatesEqual($checkout, $state1, $state2)) {
                    $this->restartCheckout($workflowItem);
                }
            }
        }

        return $checkout;
    }

    /**
     * @throws WorkflowException
     * @throws Exception
     */
    protected function restartCheckout(WorkflowItem $workflowItem): void
    {
        $workflowName = $workflowItem->getWorkflowName();
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        $this->workflowManager->resetWorkflowItem($workflowItem);
        $this->workflowManager->startWorkflow($workflowName, $checkout);

        $shoppingList = $checkout->getSource()->getShoppingList();
        $this->startShoppingListCheckoutAction($shoppingList, true);
    }

    private function startShoppingListCheckoutAction(ShoppingList $shoppingList, $forceStartCheckout = false): void
    {
        $actionData = new ActionData(['shoppingList' => $shoppingList, 'forceStartCheckout' => $forceStartCheckout]);
        $this->actionGroupRegistry->findByName('start_shoppinglist_checkout')?->execute($actionData);
    }
}
