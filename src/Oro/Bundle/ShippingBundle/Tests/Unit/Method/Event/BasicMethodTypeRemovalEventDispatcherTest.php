<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Event;

use Oro\Bundle\ShippingBundle\Method\Event\BasicMethodTypeRemovalEventDispatcher;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeRemovalEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodTypeRemovalEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var BasicMethodTypeRemovalEventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dispatcher = new BasicMethodTypeRemovalEventDispatcher($this->eventDispatcher);
    }

    public function testDispatch()
    {
        $methodId = 'method';
        $typeId = 'type';
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new MethodTypeRemovalEvent($methodId, $typeId), MethodTypeRemovalEvent::NAME);

        $this->dispatcher->dispatch($methodId, $typeId);
    }
}
