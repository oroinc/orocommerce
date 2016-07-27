<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutStepsDataProvider
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
     * @param Checkout $checkout
     *
     * @return Collection|Step[]
     * @throws WorkflowException
     */
    public function getSteps(Checkout $checkout)
    {
        $workflowItem = $checkout->getWorkflowItem();

        $workflow = $this->workflowManager->getWorkflow($workflowItem);

        if ($workflow->getDefinition()->isStepsDisplayOrdered()) {
            $steps = $workflow->getStepManager()->getOrderedSteps();
        } else {
            $steps = $workflow->getPassedStepsByWorkflowItem($workflowItem);
        }

        return $steps;
    }
}
