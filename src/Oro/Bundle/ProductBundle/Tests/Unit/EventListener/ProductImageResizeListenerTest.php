<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ProductImageResizeListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_IMAGE_ID = 1;
    const FORCE_OPTION = false;

    /**
     * @var ProductImageResizeListener
     */
    protected $listener;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $producer;

    public function setUp()
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->listener = new ProductImageResizeListener($this->producer);
    }

    public function testResizeProductImage()
    {
        $this->assertInstanceOf(OptionalListenerInterface::class, $this->listener);

        $event = $this->prepareEvent();

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                ProductImageResizeListener::IMAGE_RESIZE_TOPIC,
                [
                    'productImageId' => self::PRODUCT_IMAGE_ID,
                    'force' => self::FORCE_OPTION
                ]
            );

        // listener should be enabled by default
        $this->listener->resizeProductImage($event);

        $this->listener->setEnabled(false);

        // producer should not be called because listener marked as disabled
        $this->listener->resizeProductImage($event);
    }

    /**
     * @return ProductImageResizeEvent
     */
    protected function prepareEvent()
    {
        $productImage = $this->prophesize(ProductImage::class);
        $productImage->getId()->willReturn(self::PRODUCT_IMAGE_ID);

        return new ProductImageResizeEvent(self::PRODUCT_IMAGE_ID, self::FORCE_OPTION);
    }
}
