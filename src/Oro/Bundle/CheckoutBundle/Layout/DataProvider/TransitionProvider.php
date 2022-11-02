<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Handles logic for fetching transitions and its data
 */
class TransitionProvider implements TransitionProviderInterface
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

    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackTransition(WorkflowItem $workflowItem)
    {
        $transitions = $this->getBackTransitions($workflowItem);

        if ($transitions) {
            return end($transitions);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackTransitions(WorkflowItem $workflowItem)
    {
        $cacheKey = $workflowItem->getId() . '_' . $workflowItem->getCurrentStep()->getId();
        if (!array_key_exists($cacheKey, $this->backTransitions)) {
            $transitions = $this->getTransitions($workflowItem, 'is_checkout_back');
            /** @var TransitionData[] $backTransitions */
            $backTransitions = [];
            foreach ($transitions as $transition) {
                $stepOrder = $transition->getStepTo()->getOrder();

                $transitionData = $this->getTransitionData($transition, $workflowItem);
                if ($transitionData) {
                    $backTransitions[$stepOrder] = $transitionData;
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
     * {@inheritdoc}
     */
    public function getContinueTransition(WorkflowItem $workflowItem, $transitionName = null)
    {
        $cacheKey = $workflowItem->getId() . '_' . $workflowItem->getCurrentStep()->getId() . '_' . $transitionName;
        if (!array_key_exists($cacheKey, $this->continueTransitions)) {
            $continueTransition = null;
            $transitions = $this->getTransitions($workflowItem, 'is_checkout_continue');

            if ($transitionName) {
                foreach ($transitions as $transition) {
                    if ($transitionName === $transition->getName()) {
                        $continueTransition = $this->getTransitionData($transition, $workflowItem);
                        break;
                    }
                }
            } else {
                foreach ($transitions as $transition) {
                    if (!$transition->isHidden()) {
                        $continueTransition = $this->getTransitionData($transition, $workflowItem);
                        if ($continueTransition) {
                            break;
                        }
                    }
                }
            }

            $this->continueTransitions[$cacheKey] = $continueTransition;
        }

        return $this->continueTransitions[$cacheKey];
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param string $frontendType
     *
     * @return Collection|Transition[]
     */
    protected function getTransitions(WorkflowItem $workflowItem, $frontendType)
    {
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);

        return $transitions->filter(
            function (Transition $transition) use ($frontendType) {
                $frontendOptions = $transition->getFrontendOptions();

                return !empty($frontendOptions[$frontendType]);
            }
        );
    }

    public function clearCache()
    {
        $this->continueTransitions = $this->backTransitions = [];
    }

    /**
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     *
     * @return TransitionData|null
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
