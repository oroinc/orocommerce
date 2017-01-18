<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Event;

use Oro\Bundle\ShippingBundle\Method\Event\BasicMethodRemovalEventDispatcher;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodRemovalEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var BasicMethodRemovalEventDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dispatcher = new BasicMethodRemovalEventDispatcher($this->eventDispatcher);
    }

    public function testDispatch()
    {
        $methodId = 'method';
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(MethodRemovalEvent::NAME, new MethodRemovalEvent($methodId));

        $this->dispatcher->dispatch($methodId);
    }
}
