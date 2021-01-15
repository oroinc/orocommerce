<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;

class CollectAutocompleteFieldsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $event = new CollectAutocompleteFieldsEvent([]);

        $this->assertEquals([], $event->getFields());

        $event->addField('test_field1');
        $event->addField('test_field2');

        $this->assertEquals(['test_field1', 'test_field2'], $event->getFields());
    }
}
