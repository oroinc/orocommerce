<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

class ProcessAutocompleteDataEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $result = new Result(new Query(), ['basic' => 'result']);

        $event = new ProcessAutocompleteDataEvent(['param1' => 'value1'], 'request', $result);

        $this->assertEquals(['param1' => 'value1'], $event->getData());
        $this->assertEquals('request', $event->getQueryString());
        $this->assertEquals($result, $event->getResult());

        $event->setData(['param2' => 'value2']);
        $this->assertEquals(['param2' => 'value2'], $event->getData());

        $newResult = new Result(new Query(), ['new' => 'result']);
        $event->setResult($newResult);
        $this->assertEquals($newResult, $event->getResult());
    }
}
