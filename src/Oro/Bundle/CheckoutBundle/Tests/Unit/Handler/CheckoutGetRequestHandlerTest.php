<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Handler;

use Oro\Bundle\CheckoutBundle\Handler\CheckoutGetRequestHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class CheckoutGetRequestHandlerTest extends TestCase
{
    private WorkflowManager|MockObject $workflowManager;
    private CheckoutGetRequestHandler $handler;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->handler = new CheckoutGetRequestHandler($this->workflowManager);
    }

    public function testIsSupported()
    {
        $getRequest = $this->createMock(Request::class);
        $getRequest->expects($this->any())
            ->method('isMethod')
            ->with(Request::METHOD_GET)
            ->willReturn(true);

        $postRequest = $this->createMock(Request::class);
        $postRequest->expects($this->any())
            ->method('isMethod')
            ->with(Request::METHOD_GET)
            ->willReturn(false);

        $this->assertTrue($this->handler->isSupported($getRequest));
        $this->assertFalse($this->handler->isSupported($postRequest));
    }

    public function testHandleWithoutTransition()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(ParameterBag::class);
        $request->query->method('has')->with('transition')->willReturn(false);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->handler->handle($workflowItem, $request);
    }

    public function testHandleWithTransitionAndNoLayoutBlockIds()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(ParameterBag::class);
        $request->query
            ->method('has')
            ->willReturnMap([
                ['transition', true],
                ['layout_block_ids', false],
            ]);
        $request->expects($this->once())
            ->method('get')
            ->with('transition')
            ->willReturn('some_transition');

        $this->workflowManager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'some_transition');

        $this->handler->handle($workflowItem, $request);
    }

    public function testHandleWithTransitionAndLayoutBlockIds()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(ParameterBag::class);
        $request->query->method('has')->willReturnMap([
            ['transition', true],
            ['layout_block_ids', true],
        ]);
        $request->expects($this->once())
            ->method('get')
            ->with('transition')
            ->willReturn('payment_error');

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->handler->handle($workflowItem, $request);
    }
}
