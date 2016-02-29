<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use OroB2B\Bundle\TaxBundle\Event\ContextEvent;
use OroB2B\Bundle\TaxBundle\Event\ContextEventDispatcher;

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
                $this->isInstanceOf('OroB2B\Bundle\TaxBundle\Event\ContextEvent')
            );

        $taxDispatcher->dispatch(new \stdClass());
    }
}
