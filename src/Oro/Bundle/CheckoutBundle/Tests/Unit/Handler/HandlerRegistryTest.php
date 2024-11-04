<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Handler;

use Oro\Bundle\CheckoutBundle\Handler\CheckoutHandlerInterface;
use Oro\Bundle\CheckoutBundle\Handler\HandlerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class HandlerRegistryTest extends TestCase
{
    private CheckoutHandlerInterface|MockObject $handler1;
    private CheckoutHandlerInterface|MockObject $handler2;
    private HandlerRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler1 = $this->createMock(CheckoutHandlerInterface::class);
        $this->handler2 = $this->createMock(CheckoutHandlerInterface::class);
        $this->registry = new HandlerRegistry([$this->handler1, $this->handler2]);
    }

    public function testIsSupported()
    {
        $request = $this->createMock(Request::class);
        $this->assertTrue($this->registry->isSupported($request));
    }

    public function testHandle()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $request = $this->createMock(Request::class);

        $this->handler1->expects($this->once())
            ->method('isSupported')
            ->with($request)
            ->willReturn(false);

        $this->handler2->expects($this->once())
            ->method('isSupported')
            ->with($request)
            ->willReturn(true);

        $this->handler2->expects($this->once())
            ->method('handle')
            ->with($workflowItem, $request);

        $this->registry->handle($workflowItem, $request);
    }

    public function testHandleWithNoSupportedHandler()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $request = $this->createMock(Request::class);

        $this->handler1->expects($this->once())
            ->method('isSupported')
            ->with($request)
            ->willReturn(false);
        $this->handler1->expects($this->never())
            ->method('handle');

        $this->handler2->expects($this->once())
            ->method('isSupported')
            ->with($request)
            ->willReturn(false);

        $this->handler2->expects($this->never())
            ->method('handle');

        $this->registry->handle($workflowItem, $request);
    }
}
