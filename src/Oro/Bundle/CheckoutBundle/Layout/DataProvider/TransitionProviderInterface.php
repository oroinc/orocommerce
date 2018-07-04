<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

interface TransitionProviderInterface
{
    /**
     * @param WorkflowItem $workflowItem
     * @return null|TransitionData
     */
    public function getBackTransition(WorkflowItem $workflowItem);

    /**
     * @param WorkflowItem $workflowItem
     * @return array
     */
    public function getBackTransitions(WorkflowItem $workflowItem);

    /**
     * @param WorkflowItem $workflowItem
     * @param string $transitionName
     * @return null|TransitionData
     */
    public function getContinueTransition(WorkflowItem $workflowItem, $transitionName = null);

    public function clearCache();
}
