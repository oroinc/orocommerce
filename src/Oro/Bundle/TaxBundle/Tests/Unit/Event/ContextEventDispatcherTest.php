<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContextEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testDispatch()
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $taxDispatcher = new ContextEventDispatcher($eventDispatcher);

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ContextEvent::class), ContextEvent::NAME);

        $taxDispatcher->dispatch(new \stdClass());
    }
}
