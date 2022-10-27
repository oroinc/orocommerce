<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductImageResizeEventTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_IMAGE_ID = 1;

    private ProductImageResizeEvent $event;

    protected function setUp(): void
    {
        $this->event = new ProductImageResizeEvent(self::PRODUCT_IMAGE_ID);
    }

    public function testGetData(): void
    {
        $expectedData = [
            'productImageId' => self::PRODUCT_IMAGE_ID,
            'force' => false,
            'dimensions' => null
        ];

        self::assertEquals($expectedData, $this->event->getData());
    }

    public function testForceSetCorrect(): void
    {
        $this->event = new ProductImageResizeEvent(self::PRODUCT_IMAGE_ID, true, []);

        self::assertTrue($this->event->getData()['force']);
    }

    public function testDimensionsSetCorrect(): void
    {
        $dimensions = ['small', 'large'];
        $this->event = new ProductImageResizeEvent(self::PRODUCT_IMAGE_ID, false, $dimensions);

        self::assertSame($dimensions, $this->event->getData()['dimensions']);
    }

    public function testGetTopicName(): void
    {
        self::assertEquals(ResizeProductImageTopic::getName(), $this->event->getTopicName());
    }
}
