<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageFactory;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductMessageHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const TOPIC = 'oro_visibility.visibility.change_product_category';

    /** @var ProductMessageFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $messageFactory;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var ProductMessageHandler */
    private $productMessageHandler;

    protected function setUp(): void
    {
        $this->messageFactory = $this->createMock(ProductMessageFactory::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->productMessageHandler = new ProductMessageHandler($this->messageFactory, $this->messageProducer);
    }

    public function testSendScheduledMessages(): void
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $message = [ProductMessageFactory::ID => 42];

        $this->messageFactory
            ->expects($this->exactly(2))
            ->method('createMessage')
            ->with($product)
            ->willReturn($message);

        // Add same message twice
        $this->productMessageHandler->addProductMessageToSchedule(self::TOPIC, $product);
        $this->productMessageHandler->addProductMessageToSchedule(self::TOPIC, $product);

        $this->messageProducer
            ->expects($this->once())
            ->method('send')
            ->with(self::TOPIC, $message);

        $this->productMessageHandler->sendScheduledMessages();
    }
}
