<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Event;

use Oro\Bundle\ShippingBundle\Method\Event\BasicMethodTypeRemovalEventDispatcher;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeRemovalEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodTypeRemovalEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var BasicMethodTypeRemovalEventDispatcher
     */
    private $dispatcher;

    protected function setUp()
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
            ->with(MethodTypeRemovalEvent::NAME, new MethodTypeRemovalEvent($methodId, $typeId));

        $this->dispatcher->dispatch($methodId, $typeId);
    }
}
