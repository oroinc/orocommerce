<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;

class ProcessAutocompleteDataEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $event = new ProcessAutocompleteDataEvent(['param1' => 'value1']);

        $this->assertEquals(['param1' => 'value1'], $event->getData());

        $event->setData(['param2' => 'value2']);

        $this->assertEquals(['param2' => 'value2'], $event->getData());
    }
}
