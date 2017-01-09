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
        $transitions = array_merge(
            $this->findByRelatedEntity($workflow, $searchContext),
            parent::getTransitions($workflow, $searchContext)
        );

        $transitions = array_filter($transitions, function (Transition $transition) {
            return !$transition->isStart();
        });

        return array_unique($transitions);
    }

    /**
     * @param Workflow $workflow
     * @param ButtonSearchContext $searchContext
     *
     * @return array
     */
    protected function findByRelatedEntity(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        if ($workflow->getDefinition()->getRelatedEntity() === $searchContext->getEntityClass() &&
            !$searchContext->getDatagrid()
        ) {
            return $workflow->getTransitionManager()->getTransitions()->toArray();
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
