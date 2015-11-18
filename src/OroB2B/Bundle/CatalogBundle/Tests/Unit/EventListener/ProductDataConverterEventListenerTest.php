<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\CatalogBundle\EventListener\AbstractProductImportEventListener;
use OroB2B\Bundle\CatalogBundle\EventListener\ProductDataConverterEventListener;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

class ProductDataConverterEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductDataConverterEventListener */
    protected $listener;

    public function setUp()
    {
        $this->listener = new ProductDataConverterEventListener();
    }

    public function tearDown()
    {
        unset($this->listener);
    }

    public function testOnBackendHeader()
    {
        $data = ['some data'];
        $event = new ProductDataConverterEvent($data);
        $this->listener->onBackendHeader($event);
        $this->assertEquals(array_merge($data, [AbstractProductImportEventListener::CATEGORY_KEY]), $event->getData());
    }
}
