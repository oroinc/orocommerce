<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

abstract class AbstractTransitionDataProvider extends AbstractServerRenderDataProvider
{
    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    public function __construct(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     * @return TransitionData|null
     */
    protected function getTransitionData(Transition $transition, WorkflowItem $workflowItem)
    {
        $errors = new ArrayCollection();
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());
        $isAllowed = $workflow->isTransitionAvailable($workflowItem, $transition, $errors);
        if ($isAllowed || !$transition->isUnavailableHidden()) {
            return new TransitionData($transition, $isAllowed, $errors);
        }

        return null;
    }
}
