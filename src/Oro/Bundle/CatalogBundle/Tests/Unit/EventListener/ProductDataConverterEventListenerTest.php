<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\EventListener\AbstractProductImportEventListener;
use Oro\Bundle\CatalogBundle\EventListener\ProductDataConverterEventListener;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

class ProductDataConverterEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductDataConverterEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductDataConverterEventListener();
    }

    public function testOnBackendHeader()
    {
        $data = ['some data'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);
        $this->assertEquals(array_merge($data, [AbstractProductImportEventListener::CATEGORY_KEY]), $event->getData());
    }
}
