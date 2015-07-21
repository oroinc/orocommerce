<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Event;

use OroB2B\Bundle\ProductBundle\Event\ProductGridWidgetRenderEvent;

class ProductGridWidgetRenderEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $data = ['test'];
        $event = new ProductGridWidgetRenderEvent($data);
        $this->assertSame($data, $event->getWidgetRouteParameters());
        $modifiedData = ['test1'];
        $event->setWidgetRouteParameters($modifiedData);
        $this->assertSame($modifiedData, $event->getWidgetRouteParameters());
    }
}
