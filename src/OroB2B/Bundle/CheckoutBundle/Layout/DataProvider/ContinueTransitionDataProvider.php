<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Layout\ContextInterface;

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
        return $this->getContinueTransition($context->data()->get('workflowItem'));
    }

    /**
     * @param WorkflowItem $workflowItem
     * @return null|TransitionData
     */
    public function getContinueTransition(WorkflowItem $workflowItem)
    {
        $cacheKey = $workflowItem->getId() . '_' . $workflowItem->getCurrentStep()->getId();
        if (!array_key_exists($cacheKey, $this->continueTransitions)) {
            $continueTransition = null;
            $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
            foreach ($transitions as $transition) {
                $frontendOptions = $transition->getFrontendOptions();
                if (!empty($frontendOptions['is_checkout_continue'])) {
                    $continueTransition = $this->getTransitionData($transition, $workflowItem);
                    if ($continueTransition) {
                        break;
                    } else {
                        continue;
                    }
                }
            }
            $this->continueTransitions[$cacheKey] = $continueTransition;
        }

        return $this->continueTransitions[$cacheKey];
    }
}
