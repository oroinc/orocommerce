<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Event;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

class ProductDataConverterEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $data = ['test'];
        $event = new ProductDataConverterEvent($data);
        $this->assertSame($data, $event->getData());
        $modifiedData = ['test1'];
        $event->setData($modifiedData);
        $this->assertSame($modifiedData, $event->getData());
        $this->assertNotSame($data, $event->getData());
    }
}
