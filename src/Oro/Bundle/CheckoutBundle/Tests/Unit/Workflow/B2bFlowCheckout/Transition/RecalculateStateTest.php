<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\RecalculateState;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecalculateStateTest extends TestCase
{
    private UpdateShippingPriceInterface|MockObject $updateShippingPrice;

    private RecalculateState $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);
        $this->transition = new RecalculateState($this->updateShippingPrice);
    }

    public function testIsPreConditionAllowedWhenCheckoutIsNotCompleted()
    {
        $checkout = new Checkout();
        $checkout->setCompleted(false);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isPreConditionAllowed($workflowItem);

        $this->assertTrue($result);
    }

    public function testIsPreConditionAllowedWhenCheckoutIsCompleted()
    {
        $checkout = new Checkout();
        $checkout->setCompleted(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isPreConditionAllowed($workflowItem);

        $this->assertFalse($result);
    }

    public function testExecute()
    {
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $data = new WorkflowData();
        $workflowItem->method('getData')->willReturn($data);

        $result = new WorkflowResult();
        $workflowItem->method('getResult')->willReturn($result);

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->transition->execute($workflowItem);

        $this->assertTrue($result->offsetGet('shippingPriceUpdated'));
        $this->assertFalse($data->offsetGet('payment_in_progress'));
    }
}
