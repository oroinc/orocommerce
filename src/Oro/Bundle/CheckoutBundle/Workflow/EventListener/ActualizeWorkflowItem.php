<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionCompletedEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Actualize workflow item from DB as event may contain a stub created during double-start.
 */
class ActualizeWorkflowItem
{
    private bool $requiresActualization = false;

    public function __construct(
        private WorkflowManager $workflowManager
    ) {
    }

    public function onTransition(TransitionEvent $event): void
    {
        $this->requiresActualization = $this->requiresActualization || !$event->getWorkflowItem()->getEntityId();
    }

    public function onComplete(TransitionCompletedEvent $event): void
    {
        if (!$this->requiresActualization) {
            return;
        }

        $newWorkflowItem = $this->getActualWorkflowItem($event);
        if ($newWorkflowItem) {
            $event->setWorkflowItem($newWorkflowItem);
        }
        $this->requiresActualization = false;
    }

    private function getActualWorkflowItem(WorkflowItemAwareEvent $event): ?WorkflowItem
    {
        $workflowItem = $event->getWorkflowItem();

        return $this->workflowManager->getWorkflowItem(
            $workflowItem->getEntity(),
            $workflowItem->getDefinition()->getName()
        );
    }
}
