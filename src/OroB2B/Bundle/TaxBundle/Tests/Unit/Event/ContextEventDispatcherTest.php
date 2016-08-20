<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;

class ContextEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatch()
    {
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher */
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $taxDispatcher = new ContextEventDispatcher($eventDispatcher);

        $eventDispatcher->expects($this->once())->method('dispatch')
            ->with(
                ContextEvent::NAME,
                $this->isInstanceOf('Oro\Bundle\TaxBundle\Event\ContextEvent')
            );

        $taxDispatcher->dispatch(new \stdClass());
    }
}
