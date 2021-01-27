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
    /** @var WorkflowItem */
    private WorkflowItem $workflowItem;

    /** @var Transition */
    private Transition $transition;

    /** @var bool */
    private bool $allowed;

    /** @var Collection */
    private Collection $errors;

    /**
     * @param WorkflowItem $workflowItem
     * @param Transition $transition
     * @param bool $isAllowed
     * @param Collection $errors
     */
    public function __construct(WorkflowItem $workflowItem, Transition $transition, bool $isAllowed, Collection $errors)
    {
        $this->workflowItem = $workflowItem;
        $this->transition = $transition;
        $this->allowed = $isAllowed;
        $this->errors = $errors;
    }

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }

    /**
     * @return Transition
     */
    public function getTransition(): Transition
    {
        return $this->transition;
    }

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * @return Collection
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }
}
