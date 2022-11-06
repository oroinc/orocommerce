<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;

class CollectContextEventTest extends \PHPUnit\Framework\TestCase
{
    public function testAddContextValue()
    {
        $event = new CollectContextEvent([]);
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

    public function notStringValueDataProvider(): array
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
     * @dataProvider notStringValueDataProvider
     */
    public function testAddContextValueWhenNameIsNotString(mixed $name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Context value name must be a string');

        $event = new CollectContextEvent([]);
        $event->addContextValue($name, 'some value');
    }

    public function testAddContextValueWhenNameIsEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Context value name cannot be empty');

        $event = new CollectContextEvent([]);
        $event->addContextValue('', 'some_value');
    }
}
