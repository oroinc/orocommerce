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
    private WorkflowItem $workflowItem;

    private Transition $transition;

    public function __construct(WorkflowItem $workflowItem, Transition $transition)
    {
        $this->workflowItem = $workflowItem;
        $this->transition = $transition;
    }

    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }

    public function getTransition(): Transition
    {
        return $this->transition;
    }
}
