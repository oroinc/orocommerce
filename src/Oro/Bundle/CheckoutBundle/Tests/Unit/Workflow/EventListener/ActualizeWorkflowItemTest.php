<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Workflow\EventListener\ActualizeWorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActualizeWorkflowItemTest extends TestCase
{
    use EntityTrait;

    private WorkflowManager|MockObject $workflowManager;
    private ActualizeWorkflowItem $listener;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->listener = new ActualizeWorkflowItem($this->workflowManager);
    }

    public function testOnTransitionRequiresActualization(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getId')
            ->willReturn(null);
        $workflowItem->expects($this->once())
            ->method('getEntityId')
            ->willReturn(null);
        $entity = new \stdClass();
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $actualWorkflowItem = $this->getEntity(WorkflowItem::class, ['id' => 1]);

        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($entity, 'test_workflow')
            ->willReturn($actualWorkflowItem);

        $this->listener->onTransition($event);
        $this->listener->onComplete($event);

        $this->assertEquals($actualWorkflowItem->getId(), $event->getWorkflowItem()->getId());
    }

    public function testOnTransitionDoesNotRequireActualization(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getEntityId')
            ->willReturn(1);

        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->onTransition($event);
        $this->workflowManager->expects($this->never())
            ->method('getWorkflowItem');
        $this->listener->onComplete($event);

        $this->assertSame($workflowItem, $event->getWorkflowItem());
    }
}