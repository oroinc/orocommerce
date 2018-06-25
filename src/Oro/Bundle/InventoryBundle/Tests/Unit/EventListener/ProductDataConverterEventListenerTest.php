<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\InventoryBundle\EventListener\ProductDataConverterEventListener;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

class ProductDataConverterEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var  ProductDataConverterEventListener */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new ProductDataConverterEventListener();
    }

    public function testModifyBackendHeader()
    {
        $event = new ProductDataConverterEvent([]);
        $this->listener->modifyBackendHeader($event);

        $this->assertEquals(['isUpcoming'], $event->getData());
    }
}
