<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

class CheckoutStepsDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var WorkflowRegistry
     */
    private $workflowRegistry;

    /**
     * @param WorkflowRegistry $workflowRegistry
     */
    public function __construct(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var WorkflowItem $workflowItem */
        $workflowItem = $context->data()->get('workflowItem');
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());

        if ($workflow->getDefinition()->isStepsDisplayOrdered()) {
            $steps = $workflow->getStepManager()->getOrderedSteps();
        } else {
            $steps = $workflow->getPassedStepsByWorkflowItem($workflowItem);
        }

        return $steps;
    }
}
