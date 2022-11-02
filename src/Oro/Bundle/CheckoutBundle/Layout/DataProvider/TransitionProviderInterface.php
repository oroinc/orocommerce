<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Provides a common interface for retrieving Checkout transitions data
 */
interface TransitionProviderInterface
{
    /**
     * Get data of Checkout reverse transition (back) by existing workflow item
     *
     * @param WorkflowItem $workflowItem
     *
     * @return null|TransitionData
     */
    public function getBackTransition(WorkflowItem $workflowItem);

    /**
     * Get an array of data of possible reverse Checkout transitions by existing workflow item
     *
     * @param WorkflowItem $workflowItem
     *
     * @return array
     */
    public function getBackTransitions(WorkflowItem $workflowItem);

    /**
     * Get data of Checkout forward transition (continue) by existing workflow item
     *
     * @param WorkflowItem $workflowItem
     * @param string $transitionName
     *
     * @return null|TransitionData
     */
    public function getContinueTransition(WorkflowItem $workflowItem, $transitionName = null);

    public function clearCache();
}
