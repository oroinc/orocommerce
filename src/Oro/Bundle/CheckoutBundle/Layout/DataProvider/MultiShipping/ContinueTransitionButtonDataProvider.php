<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Provides specific transition button options.
 */
class ContinueTransitionButtonDataProvider
{
    private const MULTI_SHIPPING_TRANSITION_JS_COMPONENT =
        'orocheckout/js/app/components/multi-shipping-transition-button-component';

    private TransitionProvider $transitionProvider;

    public function __construct(TransitionProvider $transitionProvider)
    {
        $this->transitionProvider = $transitionProvider;
    }

    /**
     * Replace js view component for "Continue" button.
     */
    public function getContinueTransition(WorkflowItem $workflowItem, ?string $transitionName = null): TransitionData
    {
        $transitionData = $this->transitionProvider->getContinueTransition($workflowItem, $transitionName);

        $transition = $transitionData->getTransition();
        $frontendOptions = $transition->getFrontendOptions();
        $frontendOptions['page_component_module'] = self::MULTI_SHIPPING_TRANSITION_JS_COMPONENT;
        $transition->setFrontendOptions($frontendOptions);

        return $transitionData;
    }
}
