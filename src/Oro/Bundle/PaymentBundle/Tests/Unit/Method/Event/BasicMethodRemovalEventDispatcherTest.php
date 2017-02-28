<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Event;

use Oro\Bundle\PaymentBundle\Method\Event\BasicMethodRenamingEventDispatcher;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodRemovalEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var BasicMethodRenamingEventDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dispatcher = new BasicMethodRenamingEventDispatcher($this->eventDispatcher);
    }

    public function testDispatch()
    {
        $oldId = 'old_id';
        $newId = 'new_id';
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(MethodRenamingEvent::NAME, new MethodRenamingEvent($oldId, $newId));

        $this->dispatcher->dispatch($oldId, $newId);
    }
}
