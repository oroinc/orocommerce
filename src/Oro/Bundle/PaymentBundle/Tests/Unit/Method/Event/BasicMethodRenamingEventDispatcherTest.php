<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Event;

use Oro\Bundle\PaymentBundle\Method\Event\BasicMethodRenamingEventDispatcher;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodRenamingEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var BasicMethodRenamingEventDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
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
            ->with(new MethodRenamingEvent($oldId, $newId), MethodRenamingEvent::NAME);

        $this->dispatcher->dispatch($oldId, $newId);
    }
}
