<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\ImportExport\Event;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategyEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $product = new Product();
        $product->setSku('SKU-1');
        $rawData = ['test'];

        $event = new ProductStrategyEvent($product, $rawData);
        $this->assertSame($product, $event->getProduct());
        $this->assertSame($rawData, $event->getRawData());
    }
}
