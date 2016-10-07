<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Event;

use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductsChangeRelationEventTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGetProducts()
    {
        $product1 = $this->getEntity(Product::class, ['id' => 777]);
        $product2 = $this->getEntity(Product::class, ['id' => 555]);

        $event = new ProductsChangeRelationEvent([$product1, $product2]);

        $this->assertEquals([$product1, $product2], $event->getProducts());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage At least one product must be passed
     */
    public function testNoProductsException()
    {
        new ProductsChangeRelationEvent([]);
    }
}
