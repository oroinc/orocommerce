<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * This transition provider returns continue transition which is always allowed, this transition is used to build
 * form for single page checkout. As transition is always allowed no field in form is disabled because of that.
 */
class SinglePageTransitionProvider extends TransitionProvider
{
    /**
     * {@inheritdoc}
     */
    public function getContinueTransition(WorkflowItem $workflowItem, $transitionName = null)
    {
        $transitionData = parent::getContinueTransition($workflowItem, $transitionName);

        return $transitionData
            ? new TransitionData($transitionData->getTransition(), true, $transitionData->getErrors())
            : null;
    }
}
