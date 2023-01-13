<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;

class ProductImageResizeListenerTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    private ProductImageResizeListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductImageResizeListener(self::getMessageProducer());
    }

    public function testShouldImplementOptionalListenerInterface(): void
    {
        self::assertInstanceOf(OptionalListenerInterface::class, $this->listener);
    }

    public function testResizeProductImage(): void
    {
        $productImageId = 123;
        $force = false;

        $this->listener->resizeProductImage(new ProductImageResizeEvent($productImageId, $force));

        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            [
                'productImageId' => $productImageId,
                'force'          => $force,
                'dimensions'     => null
            ]
        );
    }

    public function testResizeProductImageForDisabledListener(): void
    {
        $this->listener->setEnabled(false);
        $this->listener->resizeProductImage(new ProductImageResizeEvent(123, false));

        self::assertMessagesEmpty(ResizeProductImageTopic::getName());
    }
}
