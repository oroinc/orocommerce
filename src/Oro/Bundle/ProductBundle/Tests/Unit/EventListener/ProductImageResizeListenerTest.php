<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

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
     * @var MessageProducerInterface
     */
    protected $producer;

    public function setUp()
    {
        $this->producer = $this->prophesize(MessageProducerInterface::class);
        $this->listener = new ProductImageResizeListener($this->producer->reveal());
    }

    public function testResizeProductImage()
    {
        $event = $this->prepareEvent();
        $this->producer->send(
            ProductImageResizeListener::IMAGE_RESIZE_TOPIC,
            [
                'productImageId' => self::PRODUCT_IMAGE_ID,
                'force' => self::FORCE_OPTION
            ]
        )->shouldBeCalled();

        $this->listener->resizeProductImage($event);
    }

    /**
     * @return ProductImageResizeEvent
     */
    protected function prepareEvent()
    {
        $productImage = $this->prophesize(ProductImage::class);
        $productImage->getId()->willReturn(self::PRODUCT_IMAGE_ID);

        return new ProductImageResizeEvent($productImage->reveal(), self::FORCE_OPTION);
    }
}
