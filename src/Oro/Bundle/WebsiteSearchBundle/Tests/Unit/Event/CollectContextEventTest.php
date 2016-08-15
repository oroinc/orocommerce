<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;

class CollectContextEventTest extends \PHPUnit_Framework_TestCase
{
    public function testAddContextValue()
    {
        $event = new CollectContextEvent();
        $stdClass = new \stdClass();
        $array = ['key' => 'value'];

        $event->addContextValue('some_string_name', 'some_string_value');
        $event->addContextValue('some_integer_name', 1);
        $event->addContextValue('some_object_name', $stdClass);
        $event->addContextValue('some_array_name', $array);

        $this->assertEquals([
            'some_string_name' => 'some_string_value',
            'some_integer_name' => 1,
            'some_object_name' => $stdClass,
            'some_array_name' => $array
        ], $event->getContext());
    }
}
