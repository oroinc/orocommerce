<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutLineItemGroupingInvalidationHelper;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\InvalidateCheckoutLineItemsGrouping;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvalidateCheckoutLineItemsGroupingTest extends TestCase
{
    private CheckoutWorkflowHelper|MockObject $checkoutWorkflowHelper;
    private CheckoutLineItemGroupingInvalidationHelper|MockObject $helper;
    private InvalidateCheckoutLineItemsGrouping $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);
        $this->helper = $this->createMock(CheckoutLineItemGroupingInvalidationHelper::class);

        $this->listener = new InvalidateCheckoutLineItemsGrouping(
            $this->checkoutWorkflowHelper,
            $this->helper
        );
    }

    public function testOnCheckoutRequestWithNotStartedWorkflow(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn(null);

        $this->helper->expects($this->never())
            ->method('shouldInvalidateLineItemGrouping');

        $this->helper->expects($this->never())
            ->method('invalidateLineItemGrouping');

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($checkout);

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithInvalidation(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->helper->expects($this->once())
            ->method('shouldInvalidateLineItemGrouping')
            ->with($workflowItem)
            ->willReturn(true);

        $this->helper->expects($this->once())
            ->method('invalidateLineItemGrouping')
            ->with($checkout, $workflowItem);

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($checkout);

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithoutInvalidation(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->helper->expects($this->once())
            ->method('shouldInvalidateLineItemGrouping')
            ->with($workflowItem)
            ->willReturn(false);

        $this->helper->expects($this->never())
            ->method('invalidateLineItemGrouping');

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($checkout);

        $this->listener->onCheckoutRequest($event);
    }
}
