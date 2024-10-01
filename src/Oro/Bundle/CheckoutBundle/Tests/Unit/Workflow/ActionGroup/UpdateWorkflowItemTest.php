<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateWorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateWorkflowItemTest extends TestCase
{
    private WorkflowManager|MockObject $workflowManager;
    private ActionExecutor|MockObject $actionExecutor;
    private UpdateWorkflowItem $updateWorkflowItem;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->updateWorkflowItem = new UpdateWorkflowItem($this->workflowManager, $this->actionExecutor);
    }

    public function testExecute(): void
    {
        $entity = new \stdClass();
        $data = ['key' => 'value'];

        $workflow = $this->createMock(Workflow::class);
        $workflowItem = $this->createMock(WorkflowItem::class);

        $workflow->expects($this->once())
            ->method('getName')
            ->willReturn('workflow_name');

        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($entity, 'workflow_name')
            ->willReturn($workflowItem);

        $workflowData = new WorkflowData();
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $workflowItem->expects($this->once())
            ->method('setUpdated');

        $this->actionExecutor->expects($this->exactly(2))
            ->method('executeAction')
            ->withConsecutive(
                ['copy_values', [$workflowData, $data]],
                ['flush_entity', [$workflowItem]]
            );

        $result = $this->updateWorkflowItem->execute($entity, $data);

        $this->assertEquals(['workflowItem' => $workflowItem, 'currentWorkflow' => $workflow], $result);
    }

    public function testExecuteNoAvailableWorkflow(): void
    {
        $entity = new \stdClass();
        $data = ['key' => 'value'];

        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn(null);

        $this->workflowManager->expects($this->never())
            ->method('getWorkflowItem');

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $result = $this->updateWorkflowItem->execute($entity, $data);

        $this->assertEquals([], $result);
    }

    public function testExecuteNoWorkflowItem(): void
    {
        $entity = new \stdClass();
        $data = ['key' => 'value'];

        $workflow = $this->createMock(Workflow::class);

        $workflow->expects($this->once())
            ->method('getName')
            ->willReturn('workflow_name');

        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($entity, 'workflow_name')
            ->willReturn(null);

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $result = $this->updateWorkflowItem->execute($entity, $data);

        $this->assertEquals([], $result);
    }
}
