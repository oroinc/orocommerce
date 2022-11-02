<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Behave as appropriate TransitionProviderInterface based on active checkout workflow.
 */
class WorkflowRelatedTransitionProvider implements TransitionProviderInterface
{
    /**
     * @var WorkflowManager
     */
    private $workflowManager;

    /**
     * @var TransitionProviderInterface
     */
    private $defaultProvider;

    /**
     * @var array|TransitionProviderInterface[]
     */
    private $workflowRelatedProviders = [];

    /**
     * @var TransitionProviderInterface
     */
    private $actualProvider;

    public function __construct(
        WorkflowManager $workflowManager,
        TransitionProviderInterface $defaultProvider
    ) {
        $this->workflowManager = $workflowManager;
        $this->defaultProvider = $defaultProvider;
    }

    public function addWorkflowRelatedProvider(string $workflowName, TransitionProviderInterface $provider): void
    {
        $this->workflowRelatedProviders[$workflowName] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getBackTransition(WorkflowItem $workflowItem)
    {
        return $this->getProvider()->getBackTransition($workflowItem);
    }

    /**
     * {@inheritDoc}
     */
    public function getBackTransitions(WorkflowItem $workflowItem)
    {
        return $this->getProvider()->getBackTransitions($workflowItem);
    }

    /**
     * {@inheritDoc}
     */
    public function getContinueTransition(WorkflowItem $workflowItem, $transitionName = null)
    {
        return $this->getProvider()->getContinueTransition($workflowItem, $transitionName);
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache()
    {
        $this->getProvider()->clearCache();
    }

    private function getProvider(): TransitionProviderInterface
    {
        if (!$this->actualProvider) {
            $actualProvider = $this->defaultProvider;
            foreach ($this->workflowRelatedProviders as $workflowName => $provider) {
                if ($this->workflowManager->isActiveWorkflow($workflowName)) {
                    $actualProvider = $provider;
                    break;
                }
            }

            $this->actualProvider = $actualProvider;
        }

        return $this->actualProvider;
    }
}
