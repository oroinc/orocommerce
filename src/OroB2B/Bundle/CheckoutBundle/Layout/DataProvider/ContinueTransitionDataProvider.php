<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class ContinueTransitionDataProvider extends AbstractTransitionDataProvider
{
    /**
     * @var array
     */
    protected $continueTransitions = [];
    
    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');

        return $this->getContinueTransition($checkout->getWorkflowItem());
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return null|TransitionData
     */
    public function getContinueTransition(WorkflowItem $workflowItem)
    {
        if (!array_key_exists($workflowItem->getId(), $this->continueTransitions)) {
            $continueTransition = null;
            $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
            foreach ($transitions as $transition) {
                $frontendOptions = $transition->getFrontendOptions();
                if (!empty($frontendOptions['is_checkout_continue'])) {
                    $continueTransition = $this->getTransitionData($transition, $workflowItem);
                    break;
                }
            }
            $this->continueTransitions[$workflowItem->getId()] = $continueTransition;
        }

        return $this->continueTransitions[$workflowItem->getId()];
    }
}
