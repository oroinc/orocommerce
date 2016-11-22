<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductImageResizeEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductImageResizeEvent
     */
    protected $event;

    /**
     * @var ProductImage
     */
    protected $productImage;

    public function setUp()
    {
        $this->productImage = new ProductImage();
        $this->event = new ProductImageResizeEvent($this->productImage);
    }

    public function testMutators()
    {
        $this->assertEquals($this->productImage, $this->event->getProductImage());
        $this->assertFalse($this->event->getForceOption());

        $this->event = new ProductImageResizeEvent($this->productImage, true);
        $this->assertTrue($this->event->getForceOption());
    }
}
