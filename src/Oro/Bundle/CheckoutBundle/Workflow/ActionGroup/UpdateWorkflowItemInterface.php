<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

/**
 * Update the data of WorkflowItem that matches passed entity.
 */
interface UpdateWorkflowItemInterface
{
    /**
     * @param object $entity
     * @param array $data
     * @return array{
     *     workflowItem?: \Oro\Bundle\WorkflowBundle\Entity\WorkflowItem,
     *     currentWorkflow?: \Oro\Bundle\WorkflowBundle\Model\Workflow
     * }
     */
    public function execute(object $entity, array $data): array;
}
