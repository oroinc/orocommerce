<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Replaces JavaScript view component for "Continue" button.
 */
class ContinueTransitionButtonDataProvider
{
    private TransitionProvider $transitionProvider;
    private string $multiShippingTransitionJsComponent;

    public function __construct(TransitionProvider $transitionProvider, string $multiShippingTransitionJsComponent)
    {
        $this->transitionProvider = $transitionProvider;
        $this->multiShippingTransitionJsComponent = $multiShippingTransitionJsComponent;
    }

    public function getContinueTransition(WorkflowItem $workflowItem, ?string $transitionName = null): TransitionData
    {
        $transitionData = $this->transitionProvider->getContinueTransition($workflowItem, $transitionName);

        $transition = $transitionData->getTransition();
        $frontendOptions = $transition->getFrontendOptions();
        $frontendOptions['page_component_module'] = $this->multiShippingTransitionJsComponent;
        $transition->setFrontendOptions($frontendOptions);

        return $transitionData;
    }
}
