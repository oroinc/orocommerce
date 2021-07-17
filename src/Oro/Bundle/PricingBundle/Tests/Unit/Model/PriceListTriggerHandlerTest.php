<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var PriceListTriggerHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->handler = new PriceListTriggerHandler($this->messageProducer);
    }

    private function getPriceList(int $id): PriceList
    {
        return $this->getEntity(PriceList::class, ['id' => $id]);
    }

    private function getProduct(int $id): Product
    {
        return $this->getEntity(Product::class, ['id' => $id]);
    }

    public function testHandleWithoutProducts()
    {
        $priceList = $this->getPriceList(1);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::RESOLVE_PRICE_RULES, ['product' => [$priceList->getId() => []]]);

        $this->handler->handlePriceListTopic(Topics::RESOLVE_PRICE_RULES, $priceList);
    }

    public function testHandleWithProductIds()
    {
        $priceList = $this->getPriceList(1);
        $productId = 11;

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::RESOLVE_PRICE_RULES, ['product' => [$priceList->getId() => [$productId]]]);

        $this->handler->handlePriceListTopic(Topics::RESOLVE_PRICE_RULES, $priceList, [$productId]);
    }

    public function testHandleWithProducts()
    {
        $priceList = $this->getPriceList(1);
        $product = $this->getProduct(11);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::RESOLVE_PRICE_RULES, ['product' => [$priceList->getId() => [$product->getId()]]]);

        $this->handler->handlePriceListTopic(Topics::RESOLVE_PRICE_RULES, $priceList, [$product]);
    }

    public function testHandleWithProductButItIsNull()
    {
        $priceList = $this->getPriceList(1);
        $product = null;

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::RESOLVE_PRICE_RULES, ['product' => [$priceList->getId() => []]]);

        $this->handler->handlePriceListTopic(Topics::RESOLVE_PRICE_RULES, $priceList, [$product]);
    }

    public function testHandleWithDisabledPriceList()
    {
        $priceList = $this->getPriceList(1);
        $priceList->setActive(false);

        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->handler->handlePriceListTopic(Topics::RESOLVE_PRICE_RULES, $priceList);
    }
}
