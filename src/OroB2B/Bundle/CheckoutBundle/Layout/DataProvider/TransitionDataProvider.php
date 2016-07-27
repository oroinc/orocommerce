<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class TransitionDataProvider
{
    /**
     * @var array
     */
    private $backTransitions = [];

    /**
     * @var array
     */
    private $continueTransitions = [];

    /**
     * @var WorkflowManager
     */
    private $workflowManager;

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
     * @return null|TransitionData
     */
    public function getBackTransition(Checkout $checkout)
    {
        $transitions = $this->getBackTransitions($checkout);

        if ($transitions) {
            return end($transitions);
        }

        return null;
    }

    /**
     * @param Checkout $checkout
     *
     * @return array
     */
    public function getBackTransitions(Checkout $checkout)
    {
        $workflowItem = $checkout->getWorkflowItem();

        $cacheKey = $workflowItem->getId() . '_' . $workflowItem->getCurrentStep()->getId();
        if (!array_key_exists($cacheKey, $this->backTransitions)) {
            $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
            /** @var TransitionData[] $backTransitions */
            $backTransitions = [];
            foreach ($transitions as $transition) {
                $frontendOptions = $transition->getFrontendOptions();
                if (!empty($frontendOptions['is_checkout_back'])) {
                    $stepOrder = $transition->getStepTo()->getOrder();

                    $transitionData = $this->getTransitionData($transition, $workflowItem);
                    if ($transitionData) {
                        $backTransitions[$stepOrder] = $transitionData;
                    }
                }
            }
            ksort($backTransitions);

            $transitions = [];
            foreach ($backTransitions as $transitionData) {
                $transitions[$transitionData->getTransition()->getStepTo()->getName()] = $transitionData;
            }

            $this->backTransitions[$cacheKey] = $transitions;
        }

        return $this->backTransitions[$cacheKey];
    }

    /**
     * @param Checkout $checkout
     *
     * @return null|TransitionData
     */
    public function getContinueTransition(Checkout $checkout)
    {
        $workflowItem = $checkout->getWorkflowItem();
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

    /**
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     *
     * @return TransitionData[]|null
     */
    private function getTransitionData(Transition $transition, WorkflowItem $workflowItem)
    {
        $errors = new ArrayCollection();
        $isAllowed = $this->workflowManager->isTransitionAvailable($workflowItem, $transition, $errors);
        if ($isAllowed || !$transition->isUnavailableHidden()) {
            return new TransitionData($transition, $isAllowed, $errors);
        }

        return null;
    }
}
