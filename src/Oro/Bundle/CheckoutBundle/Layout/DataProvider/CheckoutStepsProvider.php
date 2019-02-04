<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Layout data provider that implements the logic of work with steps on checkout
 */
class CheckoutStepsProvider
{
    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param array $excludedStepNames
     *
     * @return Collection|Step[]
     * @throws WorkflowException
     */
    public function getSteps(WorkflowItem $workflowItem, array $excludedStepNames = [])
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);

        if ($workflow->getDefinition()->isStepsDisplayOrdered()) {
            $steps = $workflow->getStepManager()->getOrderedSteps(true);
        } else {
            $steps = $workflow->getPassedStepsByWorkflowItem($workflowItem);
        }

        $steps = $steps->filter(function (Step $step) use ($excludedStepNames) {
            return !in_array($step->getName(), $excludedStepNames, true);
        });

        return $steps;
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param string $stepName
     * @param array $excludedStepNames
     *
     * @return int|null
     *
     */
    public function getStepOrder(WorkflowItem $workflowItem, $stepName, array $excludedStepNames = [])
    {
        $steps = $this->getSteps($workflowItem, $excludedStepNames);

        $i = 0;
        foreach ($steps as $step) {
            $i++;
            if ($step->getName() === $stepName) {
                return $i;
            }
        }

        return null;
    }
}
