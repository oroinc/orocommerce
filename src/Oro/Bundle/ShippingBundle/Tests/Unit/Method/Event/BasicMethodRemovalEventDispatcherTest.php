<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Event;

use Oro\Bundle\ShippingBundle\Method\Event\BasicMethodRemovalEventDispatcher;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodRemovalEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var BasicMethodRemovalEventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dispatcher = new BasicMethodRemovalEventDispatcher($this->eventDispatcher);
    }

    public function testDispatch()
    {
        $methodId = 'method';
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new MethodRemovalEvent($methodId), MethodRemovalEvent::NAME);

        $this->dispatcher->dispatch($methodId);
    }
}
