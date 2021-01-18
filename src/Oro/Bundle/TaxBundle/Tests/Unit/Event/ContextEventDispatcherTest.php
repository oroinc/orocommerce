<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContextEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testDispatch()
    {
        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $taxDispatcher = new ContextEventDispatcher($eventDispatcher);

        $eventDispatcher->expects($this->once())->method('dispatch')
            ->with(
                $this->isInstanceOf('Oro\Bundle\TaxBundle\Event\ContextEvent'),
                ContextEvent::NAME
            );

        $taxDispatcher->dispatch(new \stdClass());
    }
}
