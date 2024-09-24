<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * This transition provider returns continue transition which is always allowed, this transition is used to build
 * form for single page checkout. As transition is always allowed no field in form is disabled because of that.
 */
class SinglePageTransitionProvider implements TransitionProviderInterface
{
    /**
     * @var TransitionProviderInterface
     */
    private $transitionProvider;

    public function __construct(TransitionProviderInterface $transitionProvider)
    {
        $this->transitionProvider = $transitionProvider;
    }

    #[\Override]
    public function getContinueTransition(WorkflowItem $workflowItem, $transitionName = null)
    {
        $transitionData = $this->transitionProvider->getContinueTransition($workflowItem, $transitionName);

        return $transitionData
            ? new TransitionData($transitionData->getTransition(), true, $transitionData->getErrors())
            : null;
    }

    #[\Override]
    public function getBackTransitions(WorkflowItem $workflowItem)
    {
        return $this->transitionProvider->getBackTransitions($workflowItem);
    }

    #[\Override]
    public function getBackTransition(WorkflowItem $workflowItem)
    {
        return $this->transitionProvider->getBackTransition($workflowItem);
    }

    #[\Override]
    public function clearCache()
    {
        $this->transitionProvider->clearCache();
    }
}
