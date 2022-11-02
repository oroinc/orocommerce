<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event to be thrown after checkout transition is made.
 */
class CheckoutTransitionAfterEvent extends Event
{
    private WorkflowItem $workflowItem;

    private Transition $transition;

    private bool $allowed;

    private Collection $errors;

    public function __construct(WorkflowItem $workflowItem, Transition $transition, bool $isAllowed, Collection $errors)
    {
        $this->workflowItem = $workflowItem;
        $this->transition = $transition;
        $this->allowed = $isAllowed;
        $this->errors = $errors;
    }

    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }

    public function getTransition(): Transition
    {
        return $this->transition;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function getErrors(): Collection
    {
        return $this->errors;
    }
}
