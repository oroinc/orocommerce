<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\InitializeGroupedLineItems;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionCompletedEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InitializeGroupedLineItemsTest extends TestCase
{
    private ConfigProvider|MockObject $multiShippingConfigProvider;
    private GroupedCheckoutLineItemsProvider|MockObject $checkoutLineItemsProvider;
    private InitializeGroupedLineItems $listener;

    protected function setUp(): void
    {
        $this->multiShippingConfigProvider = $this->createMock(ConfigProvider::class);
        $this->checkoutLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);

        $this->listener = new InitializeGroupedLineItems(
            $this->multiShippingConfigProvider,
            $this->checkoutLineItemsProvider
        );
    }

    public function testOnCompleteWithNonStartTransition(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(false);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->never())
            ->method('getEntity');

        $this->multiShippingConfigProvider->expects($this->never())
            ->method('isLineItemsGroupingEnabled');

        $this->checkoutLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $event = new TransitionCompletedEvent($workflowItem, $transition);
        $this->listener->onComplete($event);
    }

    public function testOnCompleteWithUnsupportedWorkflowItemEntity(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(true);

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getMetadata')
            ->willReturn([]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->checkoutLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $event = new TransitionCompletedEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnCompleteWithNonCheckoutEntity(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(true);

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getMetadata')
            ->willReturn(['is_checkout_workflow' => true]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $this->checkoutLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $event = new TransitionCompletedEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnCompleteWithValidConfigAndGroupingDisabled(): void
    {
        $checkout = new Checkout();

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(true);

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getMetadata')
            ->willReturn(['is_checkout_workflow' => true]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(false);

        $this->checkoutLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $event = new TransitionCompletedEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnComplete(): void
    {
        $checkout = new Checkout();

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(true);

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getMetadata')
            ->willReturn(['is_checkout_workflow' => true]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $data = new WorkflowData();
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->multiShippingConfigProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $groupedIds = [1, 2];
        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsIds')
            ->willReturn($groupedIds);

        $event = new TransitionCompletedEvent($workflowItem, $transition);

        $this->listener->onComplete($event);

        $this->assertEquals($groupedIds, $data->offsetGet('grouped_line_items'));
    }
}
