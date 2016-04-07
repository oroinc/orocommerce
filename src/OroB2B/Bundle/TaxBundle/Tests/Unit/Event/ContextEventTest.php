<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Event;

use OroB2B\Bundle\TaxBundle\Event\ContextEvent;

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
        $this->assertEquals('orob2b_tax.mapper.context', ContextEvent::NAME);
    }
}
