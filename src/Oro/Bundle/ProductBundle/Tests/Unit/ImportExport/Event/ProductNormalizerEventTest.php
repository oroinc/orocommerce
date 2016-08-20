<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Event;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $product = new Product();
        $product->setSku('SKU-1');
        $plainData = ['test'];
        $context = ['contextData'];

        $event = new ProductNormalizerEvent($product, $plainData, $context);
        $this->assertSame($product, $event->getProduct());
        $this->assertSame($plainData, $event->getPlainData());
        $this->assertSame($context, $event->getContext());

        $modifiedPlainData = ['test1'];
        $event->setPlainData($modifiedPlainData);
        $this->assertSame($modifiedPlainData, $event->getPlainData());
        $this->assertNotSame($plainData, $event->getPlainData());
    }
}
