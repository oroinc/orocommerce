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

    /**
     * @return array
     */
    public function notStringValueDataProvider()
    {
        return [
            [0],
            [1.5],
            [null],
            [[1, 2]],
            [new \stdClass()],
        ];
    }

    /**
     * @param mixed $name
     * @dataProvider notStringValueDataProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Context value name must be a string
     * @throws \InvalidArgumentException
     */
    public function testAddContextValueWhenNameIsNotString($name)
    {
        $event = new CollectContextEvent();
        $event->addContextValue($name, 'some value');
    }
}
