<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Update the data of WorkflowItem that matches passed entity.
 */
class UpdateWorkflowItem implements UpdateWorkflowItemInterface
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private ActionExecutor $actionExecutor
    ) {
    }

    #[\Override]
    public function execute(
        object $entity,
        array $data
    ): array {
        $currentWorkflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(
            Checkout::class,
            'b2b_checkout_flow'
        );
        if (!$currentWorkflow) {
            return [];
        }

        $workflowItem = $this->workflowManager->getWorkflowItem($entity, $currentWorkflow->getName());
        if (!$workflowItem) {
            return [];
        }

        $this->actionExecutor->executeAction(
            'copy_values',
            [$workflowItem->getData(), $data]
        );

        $workflowItem->setUpdated();

        $this->actionExecutor->executeAction(
            'flush_entity',
            [$workflowItem]
        );

        return ['workflowItem' => $workflowItem, 'currentWorkflow' => $currentWorkflow];
    }
}
