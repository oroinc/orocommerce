<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event to be thrown before checkout transition is made.
 */
class CheckoutTransitionBeforeEvent extends Event
{
    public function __construct(
        private WorkflowItem $workflowItem,
        private Transition $transition
    ) {
    }

    public function getTransition(): Transition
    {
        return $this->transition;
    }

    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }
}
