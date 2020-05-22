<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ProductImageResizeListenerTest extends \PHPUnit\Framework\TestCase
{
    const PRODUCT_IMAGE_ID = 1;
    const FORCE_OPTION = false;

    /** @var ProductImageResizeListener */
    protected $listener;

    /** @var MessageProducerInterface|MockObject */
    protected $producer;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->listener = new ProductImageResizeListener($this->producer);
    }

    public function testResizeProductImage()
    {
        static::assertInstanceOf(OptionalListenerInterface::class, $this->listener);

        $event = $this->prepareEvent();

        $this->producer->expects(static::once())
            ->method('send')
            ->with(
                ProductImageResizeListener::IMAGE_RESIZE_TOPIC,
                [
                    'productImageId' => self::PRODUCT_IMAGE_ID,
                    'force' => self::FORCE_OPTION,
                    'dimensions' => null
                ]
            );

        // listener should be enabled by default
        $this->listener->resizeProductImage($event);

        $this->listener->setEnabled(false);

        // producer should not be called because listener marked as disabled
        $this->listener->resizeProductImage($event);
    }

    protected function prepareEvent(): ProductImageResizeEvent
    {
        $productImage = $this->createMock(ProductImage::class);
        $productImage->method('getId')->willReturn(self::PRODUCT_IMAGE_ID);

        return new ProductImageResizeEvent(self::PRODUCT_IMAGE_ID, self::FORCE_OPTION);
    }
}
