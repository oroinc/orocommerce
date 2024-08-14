<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Handler\CheckoutHandlerInterface;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\ProcessWorkflowRequestEventListener;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ProcessWorkflowRequestEventListenerTest extends TestCase
{
    private CheckoutWorkflowHelper|MockObject $checkoutWorkflowHelper;
    private WorkflowManager|MockObject $workflowManager;
    private CheckoutHandlerInterface|MockObject $checkoutHandler;
    private ProcessWorkflowRequestEventListener $listener;

    protected function setUp(): void
    {
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->checkoutHandler = $this->createMock(CheckoutHandlerInterface::class);

        $this->listener = new ProcessWorkflowRequestEventListener(
            $this->checkoutWorkflowHelper,
            $this->workflowManager,
            $this->checkoutHandler
        );
    }

    public function testOnCheckoutRequestWithVerifyTransition(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowStep = $this->createMock(WorkflowStep::class);
        $request = $this->createMock(Request::class);
        $event = $this->createMock(CheckoutRequestEvent::class);
        $verifyTransition = $this->createMock(Transition::class);

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->checkoutHandler->expects($this->once())
            ->method('handle')
            ->with($workflowItem, $request);

        $verifyTransition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_verify' => true]);

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->willReturn([$verifyTransition]);

        $this->workflowManager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, $verifyTransition);

        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($workflowStep);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($checkout);

        $event->expects($this->once())
            ->method('setWorkflowStep')
            ->with($workflowStep);

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithoutVerifyTransition(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowStep = $this->createMock(WorkflowStep::class);
        $request = $this->createMock(Request::class);
        $event = $this->createMock(CheckoutRequestEvent::class);
        $transition = $this->createMock(Transition::class);

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->checkoutHandler->expects($this->once())
            ->method('handle')
            ->with($workflowItem, $request);

        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn([]);

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->willReturn([$transition]);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($workflowStep);

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($checkout);

        $event->expects($this->once())
            ->method('setWorkflowStep')
            ->with($workflowStep);

        $this->listener->onCheckoutRequest($event);
    }
}
