<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Event;

use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductsChangeRelationEventTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetProducts()
    {
        $product1 = $this->getEntity(Product::class, ['id' => 777]);
        $product2 = $this->getEntity(Product::class, ['id' => 555]);

        $event = new ProductsChangeRelationEvent([$product1, $product2]);

        $this->assertEquals([$product1, $product2], $event->getProducts());
    }

    public function testNoProductsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one product must be passed');

        new ProductsChangeRelationEvent([]);
    }
}
