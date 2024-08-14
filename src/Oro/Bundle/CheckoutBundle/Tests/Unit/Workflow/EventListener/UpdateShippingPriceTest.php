<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\UpdateShippingPrice;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateShippingPriceTest extends TestCase
{
    private UpdateShippingPriceInterface|MockObject $updateShippingPrice;
    private UpdateShippingPrice $listener;

    protected function setUp(): void
    {
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);
        $this->listener = new UpdateShippingPrice($this->updateShippingPrice);
    }

    public function testUpdateShippingPriceWhenNotCheckoutWorkflow(): void
    {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getMetadata')
            ->willReturn([]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->never())
            ->method('getFrontendOptions');

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->updateShippingPrice($event);
    }

    public function testUpdateShippingPriceWhenNotContinueTransition(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getMetadata')
            ->willReturn(['is_checkout_workflow' => true]);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_continue' => false]);

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->updateShippingPrice($event);
    }

    public function testUpdateShippingPriceWhenShippingPriceAlreadyUpdated(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionCall($workflowItem);

        $transition = $this->prepareTransition();

        $result = $this->createMock(\ArrayObject::class);
        $result->expects($this->once())
            ->method('offsetGet')
            ->with('shippingPriceUpdated')
            ->willReturn(true);

        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->updateShippingPrice($event);
    }

    public function testUpdateShippingPriceWhenShippingDataNotReady(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionCall($workflowItem);

        $transition = $this->prepareTransition();

        $result = $this->createMock(\ArrayObject::class);
        $result->expects($this->once())
            ->method('offsetGet')
            ->with('shippingPriceUpdated')
            ->willReturn(false);

        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);

        $data = $this->createMock(\ArrayObject::class);
        $data->expects($this->once())
            ->method('offsetGet')
            ->with('shipping_data_ready')
            ->willReturn(false);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->updateShippingPrice($event);
    }

    public function testUpdateShippingPriceWhenNoShippingMethod(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionCall($workflowItem);

        $transition = $this->prepareTransition();

        $result = $this->createMock(\ArrayObject::class);
        $result->expects($this->once())
            ->method('offsetGet')
            ->with('shippingPriceUpdated')
            ->willReturn(false);

        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);

        $data = $this->createMock(\ArrayObject::class);
        $data->expects($this->once())
            ->method('offsetGet')
            ->with('shipping_data_ready')
            ->willReturn(true);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn(null);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->updateShippingPrice($event);
    }

    public function testUpdateShippingPrice(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionCall($workflowItem);

        $transition = $this->prepareTransition();

        $result = $this->createMock(\ArrayObject::class);
        $result->expects($this->once())
            ->method('offsetGet')
            ->with('shippingPriceUpdated')
            ->willReturn(false);

        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);

        $data = $this->createMock(\ArrayObject::class);
        $data->expects($this->once())
            ->method('offsetGet')
            ->with('shipping_data_ready')
            ->willReturn(true);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn('shipping_method');

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $result->expects($this->once())
            ->method('offsetSet')
            ->with('shippingPriceUpdated', true);

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->updateShippingPrice($event);
    }

    private function assertDefinitionCall(WorkflowItem|MockObject $workflowItem): void
    {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getMetadata')
            ->willReturn(['is_checkout_workflow' => true]);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);
    }

    private function prepareTransition(): Transition|MockObject
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_continue' => true]);

        return $transition;
    }
}
