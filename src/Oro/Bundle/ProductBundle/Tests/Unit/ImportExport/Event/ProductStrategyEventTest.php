<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Event;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategyEventTest extends \PHPUnit\Framework\TestCase
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

    public function testProductValidByDefault()
    {
        $product = new Product();
        $product->setSku('SKU-1');
        $rawData = ['test'];

        $event = new ProductStrategyEvent($product, $rawData);

        $this->assertTrue($event->isProductValid());
    }

    public function testMarkProductInvalid()
    {
        $product = new Product();
        $product->setSku('SKU-1');
        $rawData = ['test'];

        $event = new ProductStrategyEvent($product, $rawData);
        $this->assertTrue($event->isProductValid());

        $event->markProductInvalid();

        $this->assertFalse($event->isProductValid());
    }

    public function testContextIsNullByDefault()
    {
        $product = new Product();
        $product->setSku('SKU-1');
        $rawData = ['test'];

        $event = new ProductStrategyEvent($product, $rawData);

        $this->assertNull($event->getContext());
    }

    public function testSetAndGetContext()
    {
        $product = new Product();
        $product->setSku('SKU-1');
        $rawData = ['test'];
        $context = new Context([]);

        $event = new ProductStrategyEvent($product, $rawData);
        $event->setContext($context);

        $this->assertSame($context, $event->getContext());
    }

    public function testEventConstants()
    {
        $this->assertEquals('oro_product.strategy.process_before', ProductStrategyEvent::PROCESS_BEFORE);
        $this->assertEquals('oro_product.strategy.process_after', ProductStrategyEvent::PROCESS_AFTER);
    }
}
