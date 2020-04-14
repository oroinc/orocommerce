<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductImageResizeEventTest extends \PHPUnit\Framework\TestCase
{
    const PRODUCT_IMAGE_ID = 1;

    /**
     * @var ProductImageResizeEvent
     */
    protected $event;

    /**
     * @var ProductImage
     */
    protected $productImageId;

    protected function setUp(): void
    {
        $this->event = new ProductImageResizeEvent(self::PRODUCT_IMAGE_ID);
    }

    public function testGetData()
    {
        $expectedData = [
            'productImageId' => self::PRODUCT_IMAGE_ID,
            'force' => false,
            'dimensions' => null
        ];

        $this->assertEquals($expectedData, $this->event->getData());
    }

    public function testForceSetCorrect()
    {
        $this->event = new ProductImageResizeEvent(self::PRODUCT_IMAGE_ID, true, []);
        $this->assertTrue($this->event->getData()['force']);
    }

    public function testDimensionsSetCorrect()
    {
        $dimensions = ['small', 'large'];
        $this->event = new ProductImageResizeEvent(self::PRODUCT_IMAGE_ID, false, $dimensions);
        $this->assertSame($dimensions, $this->event->getData()['dimensions']);
    }
}
