<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\EntityListener\ProductListener;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;

class ProductListenerTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC = 'oro_visibility.visibility.change_product_category';

    /** @var VisibilityMessageHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $visibilityChangeMessageHandler;

    /** @var ProductListener */
    private $visibilityListener;

    protected function setUp(): void
    {
        $this->visibilityChangeMessageHandler = $this->createMock(VisibilityMessageHandler::class);

        $this->visibilityListener = new ProductListener($this->visibilityChangeMessageHandler);
        $this->visibilityListener->setTopic(self::TOPIC);
    }

    public function testPostPersist(): void
    {
        $product = $this->createMock(Product::class);

        $this->visibilityChangeMessageHandler
            ->expects($this->once())
            ->method('addMessageToSchedule')
            ->with(self::TOPIC, $product);

        $this->visibilityListener->postPersist($product);
    }

    public function testPreUpdate(): void
    {
        $product = $this->createMock(Product::class);

        $this->visibilityChangeMessageHandler
            ->expects($this->once())
            ->method('addMessageToSchedule')
            ->with(self::TOPIC, $product);

        $this->visibilityListener->preUpdate($product);
    }

    public function testPreRemove(): void
    {
        $product = $this->createMock(Product::class);

        $this->visibilityChangeMessageHandler
            ->expects($this->once())
            ->method('addMessageToSchedule')
            ->with(self::TOPIC, $product);

        $this->visibilityListener->preRemove($product);
    }
}
