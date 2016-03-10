<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class BackTransitionsDataProvider extends AbstractTransitionDataProvider
{
    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');

        return  $this->getBackTransitions($checkout->getWorkflowItem());
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return null|Transition
     */
    protected function getBackTransitions(WorkflowItem $workflowItem)
    {
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
        $backTransitions = [];
        foreach ($transitions as $transition) {
            $frontendOptions = $transition->getFrontendOptions();
            if (!empty($frontendOptions['is_checkout_back'])) {
                $stepOrder = $transition->getStepTo()->getOrder();
                $backTransitions[$stepOrder] = $this->getTransitionData($transition, $workflowItem);
            }
        }
        ksort($backTransitions);

        return array_values($backTransitions);
    }
}
