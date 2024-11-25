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
    public function __construct(
        private WorkflowItem $workflowItem,
        private Transition $transition,
        private bool $allowed,
        private ?Collection $errors = null
    ) {
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

    public function setAllowed(bool $isAllowed): void
    {
        $this->allowed = $isAllowed;
    }

    public function getErrors(): ?Collection
    {
        return $this->errors;
    }
}
