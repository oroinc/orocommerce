<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
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

    #[\Override]
    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->handler = new PriceListTriggerHandler($this->messageProducer);
    }

    private function getPriceList(int $id, int $organizationId = 1): PriceList
    {
        $organization = new Organization();
        $organization->setId($organizationId);

        return $this->getEntity(PriceList::class, ['id' => $id, 'organization' => $organization]);
    }

    private function getProduct(int $id, int $organizationId = 1): Product
    {
        $organization = new Organization();
        $organization->setId($organizationId);

        return $this->getEntity(Product::class, ['id' => $id, 'organization' => $organization]);
    }

    public function testHandleWithoutProducts(): void
    {
        $priceList = $this->getPriceList(1);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(ResolvePriceRulesTopic::getName(), ['product' => [$priceList->getId() => []]]);

        $this->handler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList);
    }

    public function testHandleWithProductIds(): void
    {
        $priceList = $this->getPriceList(1);
        $productId = 11;

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(ResolvePriceRulesTopic::getName(), ['product' => [$priceList->getId() => [$productId]]]);

        $this->handler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList, [$productId]);
    }

    public function testHandleWithProducts(): void
    {
        $priceList = $this->getPriceList(1);
        $product = $this->getProduct(11);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ResolvePriceRulesTopic::getName(),
                ['product' => [$priceList->getId() => [$product->getId()]]]
            );

        $this->handler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList, [$product]);
    }

    public function testHandleWithProductButItIsNull(): void
    {
        $priceList = $this->getPriceList(1);
        $product = null;

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(ResolvePriceRulesTopic::getName(), ['product' => [$priceList->getId() => []]]);

        $this->handler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList, [$product]);
    }

    public function testHandleWithDisabledPriceList(): void
    {
        $priceList = $this->getPriceList(1);
        $priceList->setActive(false);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->handler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList);
    }

    public function testHandleWithProductsFromDifferentOrganizaitons(): void
    {
        $priceList = $this->getPriceList(1, 2);
        $product1 = $this->getProduct(11, 1);
        $product2 = $this->getProduct(12, 2);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                ResolvePriceRulesTopic::getName(),
                ['product' => [$priceList->getId() => [$product2->getId()]]]
            );

        $this->handler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList, [$product1, $product2]);
    }

    public function testHandleWithProductFromAnotherOrganizaiton(): void
    {
        $priceList = $this->getPriceList(1, 2);
        $product = $this->getProduct(11, 1);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->handler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList, [$product]);
    }
}
