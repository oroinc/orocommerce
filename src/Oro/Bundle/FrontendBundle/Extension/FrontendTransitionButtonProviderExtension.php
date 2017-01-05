<?php

namespace Oro\Bundle\FrontendBundle\Extension;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;

use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider;

use Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class FrontendTransitionButtonProviderExtension extends TransitionButtonProviderExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getTransitions(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        if ($workflow->getDefinition()->getRelatedEntity() === $searchContext->getEntityClass()) {
            $transitions = $workflow->getTransitionManager()->getTransitions()->toArray();

            return array_filter($transitions, function (Transition $transition) {
                return !$transition->isStart();
            });
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getApplication()
    {
        return ActionCurrentApplicationProvider::COMMERCE_APPLICATION;
    }
}
