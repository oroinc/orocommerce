<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductImageResizeEventTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_IMAGE_ID = 1;

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
        $this->productImage = $this->prophesize(ProductImage::class);
        $this->productImage->getId()->willReturn(self::PRODUCT_IMAGE_ID);
        $this->event = new ProductImageResizeEvent($this->productImage->reveal());
    }

    public function testGetData()
    {
        $expectedData = [
            'productImageId' => self::PRODUCT_IMAGE_ID,
            'force' => false
        ];

        $this->assertEquals($expectedData, $this->event->getData());

        $this->event = new ProductImageResizeEvent($this->productImage->reveal(), true);
        $this->assertTrue($this->event->getData()['force']);
    }
}
