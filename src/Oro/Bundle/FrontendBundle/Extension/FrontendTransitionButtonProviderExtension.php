<?php

namespace Oro\Bundle\FrontendBundle\Extension;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;

use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider as FrontendApplicationProvider;

use Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class FrontendTransitionButtonProviderExtension extends TransitionButtonProviderExtension
{
    /** @var CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /**
     * @param CurrentApplicationProviderInterface $applicationProvider
     *
     * @return $this
     */
    public function setApplicationProvider(CurrentApplicationProviderInterface $applicationProvider)
    {
        $this->applicationProvider = $applicationProvider;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTransitions(Workflow $workflow, ButtonSearchContext $searchContext)
    {
        if ((null === $this->applicationProvider) ||
            (FrontendApplicationProvider::COMMERCE_APPLICATION !== $this->applicationProvider->getCurrentApplication())
        ) {
            return [];
        }

        $transitions = $workflow->getTransitionManager()->getTransitions()->toArray();

        return array_filter($transitions, function (Transition $transition) {
            return !$transition->isStart();
        });
    }
}
