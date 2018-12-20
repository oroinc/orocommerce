<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Layout data provider that implements the logic of work with steps on checkout
 */
class CheckoutStepsProvider
{
    use FeatureCheckerHolderTrait;

    const CUSTOMER_CONSENTS_STEP = 'customer_consents';

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
     *
     * @return Collection|Step[]
     * @throws WorkflowException
     */
    public function getSteps(WorkflowItem $workflowItem)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);

        if ($workflow->getDefinition()->isStepsDisplayOrdered()) {
            $steps = $workflow->getStepManager()->getOrderedSteps(true);
        } else {
            $steps = $workflow->getPassedStepsByWorkflowItem($workflowItem);
        }

        return $steps;
    }

    /**
     * @param array $excludedSteps
     *
     * @return array
     */
    public function getExcludedSteps(array $excludedSteps = [])
    {
        if (!$this->isFeaturesEnabled()) {
            $excludedSteps[] = self::CUSTOMER_CONSENTS_STEP;
        }

        return $excludedSteps;
    }

    /**
     * @param int $actualStep
     *
     * @return int
     */
    public function getStepOrder($actualStep)
    {
        if (!$this->isFeaturesEnabled()) {
            --$actualStep;
        }

        return $actualStep;
    }
}
