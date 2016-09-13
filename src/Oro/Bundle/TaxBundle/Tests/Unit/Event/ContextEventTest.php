<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Oro\Bundle\TaxBundle\Event\ContextEvent;

class ContextEventTest extends \PHPUnit_Framework_TestCase
{
    public function testA()
    {
        $object = new \stdClass();
        $event = new ContextEvent($object);
        $this->assertEquals($object, $event->getMappingObject());
        $this->assertEmpty($event->getContext());
    }

    public function testEventName()
    {
        $this->assertEquals('oro_tax.mapper.context', ContextEvent::NAME);
    }
}
