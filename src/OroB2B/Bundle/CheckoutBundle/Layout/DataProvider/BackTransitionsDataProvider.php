<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class BackTransitionsDataProvider extends AbstractTransitionDataProvider
{
    /**
     * @var array
     */
    protected $transitions = [];

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');
        $workflowItem = $checkout->getWorkflowItem();

        if (!array_key_exists($workflowItem->getId(), $this->transitions)) {
            $this->transitions[$workflowItem->getId()] = $this->getBackTransitions($workflowItem);
        }

        return $this->transitions[$workflowItem->getId()];
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return null|Transition
     */
    protected function getBackTransitions(WorkflowItem $workflowItem)
    {
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
        /** @var TransitionData[] $backTransitions */
        $backTransitions = [];
        foreach ($transitions as $transition) {
            $frontendOptions = $transition->getFrontendOptions();
            if (!empty($frontendOptions['is_checkout_back'])) {
                $stepOrder = $transition->getStepTo()->getOrder();
                $backTransitions[$stepOrder] = $this->getTransitionData($transition, $workflowItem);
            }
        }
        ksort($backTransitions);

        $transitions = [];
        foreach ($backTransitions as $transitionData) {
            $transitions[$transitionData->getTransition()->getStepTo()->getName()] = $transitionData;
        }

        return $transitions;
    }
}
