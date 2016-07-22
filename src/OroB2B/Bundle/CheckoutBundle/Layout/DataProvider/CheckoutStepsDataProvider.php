<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
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
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');
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
