<?php

namespace Oro\Bundle\CheckoutBundle\Helper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShoppingListLineItemDiffMapper;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * To compare two checkout line item collections if any differences existed.
 */
class CheckoutCompareHelper
{
    private bool $restartInProgress = false;

    public function __construct(
        private CheckoutDiffStorageInterface $diffStorage,
        private ShoppingListLineItemDiffMapper $diffMapper,
        private WorkflowManager $workflowManager
    ) {
    }

    /**
     * @throws WorkflowException
     */
    public function resetCheckoutIfSourceLineItemsChanged(?Checkout $checkout, ?Checkout $rawCheckout): ?Checkout
    {
        if ($this->restartInProgress || $checkout === null || $rawCheckout === null) {
            return $checkout;
        }

        $workflowItem = $this->getWorkflowItem($checkout);
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

        return $checkout;
    }

    protected function restartCheckout(WorkflowItem $workflowItem): void
    {
        $this->restartInProgress = true;
        $this->workflowManager->transitWithoutChecks($workflowItem, TransitionManager::DEFAULT_START_TRANSITION_NAME);
        $this->restartInProgress = false;
    }

    private function getWorkflowItem(Checkout $checkout): WorkflowItem
    {
        $items = $this->workflowManager->getWorkflowItemsByEntity($checkout);
        if (count($items) !== 1) {
            throw new NotFoundHttpException('Unable to find correct WorkflowItem for current checkout');
        }

        return reset($items);
    }
}
