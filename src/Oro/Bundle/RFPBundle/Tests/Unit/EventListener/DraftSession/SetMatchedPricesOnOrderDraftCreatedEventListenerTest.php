<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitItemLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\DraftSession\SetMatchedPricesOnOrderDraftCreatedEventListener;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetMatchedPricesOnOrderDraftCreatedEventListenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private ProductLineItemPriceProviderInterface&MockObject $productLineItemPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface&MockObject $priceScopeCriteriaFactory;

    private SetMatchedPricesOnOrderDraftCreatedEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->productLineItemPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);

        $this->listener = new SetMatchedPricesOnOrderDraftCreatedEventListener(
            $this->productLineItemPriceProvider,
            $this->priceScopeCriteriaFactory,
        );

        $this->setUpLoggerMock($this->listener);
    }

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotRequest(): void
    {
        $notARequest = $this->createMock(EntityDraftAwareInterface::class);
        $orderDraft = new Order();
        $event = new EntityDraftCreatedEvent($notARequest, $orderDraft);

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $request = new Request();
        $notAnOrder = $this->createMock(EntityDraftAwareInterface::class);
        $event = new EntityDraftCreatedEvent($request, $notAnOrder);

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedDoesNothingWhenNoUnpricedLineItems(): void
    {
        $request = new Request();
        $orderDraft = new Order();

        $pricedLineItem = new OrderLineItem();
        $pricedLineItem->setProduct(new Product());
        $pricedLineItem->setPrice(Price::create('10', 'USD'));
        $orderDraft->addLineItem($pricedLineItem);

        $lineItemWithoutProduct = new OrderLineItem();
        $orderDraft->addLineItem($lineItemWithoutProduct);

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedSetsLineItemPrice(): void
    {
        $request = new Request();
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $product = new Product();
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $orderDraft->addLineItem($lineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $originalPrice = Price::create('25', 'USD');
        $lineItemPrice = new ProductLineItemPrice($lineItem, $originalPrice, 25.0);

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([0 => $lineItem], $scopeCriteria, 'USD')
            ->willReturn([0 => $lineItemPrice]);

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);

        self::assertNotNull($lineItem->getPrice());
        self::assertNotSame($originalPrice, $lineItem->getPrice());
        self::assertSame('25', $lineItem->getPrice()->getValue());
        self::assertSame('USD', $lineItem->getPrice()->getCurrency());
    }

    public function testOnEntityDraftCreatedSetsKitItemPrices(): void
    {
        $request = new Request();
        $orderDraft = new Order();
        $orderDraft->setCurrency('EUR');

        $product = new Product();
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);

        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 5);

        $kitItemLineItem = new OrderProductKitItemLineItem();
        // Set kitItemId before kitItem to avoid calling getDefaultLabel() (extended field)
        // in updateKitItemFallbackFields().
        $kitItemLineItem->setKitItemId(5);
        $kitItemLineItem->setKitItem($kitItem);
        $lineItem->addKitItemLineItem($kitItemLineItem);

        $orderDraft->addLineItem($lineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $originalLineItemPrice = Price::create('100', 'EUR');
        $originalKitItemPrice = Price::create('20', 'EUR');

        $kitItemLineItemPrice = new ProductKitItemLineItemPrice($kitItemLineItem, $originalKitItemPrice, 20.0);

        $kitLineItemPrice = new ProductKitLineItemPrice($lineItem, $originalLineItemPrice, 100.0);
        $kitLineItemPrice->addKitItemLineItemPrice($kitItemLineItemPrice);

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->willReturn([0 => $kitLineItemPrice]);

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);

        self::assertNotNull($lineItem->getPrice());
        self::assertSame('100', $lineItem->getPrice()->getValue());
        self::assertNotNull($kitItemLineItem->getPrice());
        self::assertSame('20', $kitItemLineItem->getPrice()->getValue());
    }

    public function testOnEntityDraftCreatedSkipsLineItemWhenNoMatchedPrice(): void
    {
        $request = new Request();
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $product = new Product();
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $orderDraft->addLineItem($lineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([0 => $lineItem], $scopeCriteria, 'USD')
            ->willReturn([]);

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);

        self::assertNull($lineItem->getPrice());
    }

    public function testOnEntityDraftCreatedLogsErrorWhenPriceFetchFails(): void
    {
        $request = new Request();
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct(new Product());
        $orderDraft->addLineItem($lineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $exception = new \RuntimeException('fetch error');
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to fetch matched prices for order draft line items.',
                ['exception' => $exception]
            );

        $this->listener->onEntityDraftCreated($event);

        self::assertNull($lineItem->getPrice());
    }

    public function testOnEntityDraftCreatedSetsZeroPriceOnFreeFormLineItems(): void
    {
        $request = new Request();
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $freeFormLineItem = new OrderLineItem();
        $freeFormLineItem->setFreeFormProduct('DELETED-SKU');
        $freeFormLineItem->setProductSku('DELETED-SKU');
        $orderDraft->addLineItem($freeFormLineItem);

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);

        self::assertNotNull($freeFormLineItem->getPrice());
        self::assertEquals(0, $freeFormLineItem->getPrice()->getValue());
        self::assertSame('USD', $freeFormLineItem->getPrice()->getCurrency());
    }

    public function testOnEntityDraftCreatedSetsZeroPriceOnFreeFormAndMatchedPricesOnRegularItems(): void
    {
        $request = new Request();
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $product = new Product();
        $regularLineItem = new OrderLineItem();
        $regularLineItem->setProduct($product);
        $orderDraft->addLineItem($regularLineItem);

        $freeFormLineItem = new OrderLineItem();
        $freeFormLineItem->setFreeFormProduct('DELETED-SKU');
        $freeFormLineItem->setProductSku('DELETED-SKU');
        $orderDraft->addLineItem($freeFormLineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $originalPrice = Price::create('15', 'USD');
        $lineItemPrice = new ProductLineItemPrice($regularLineItem, $originalPrice, 15.0);

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([0 => $regularLineItem], $scopeCriteria, 'USD')
            ->willReturn([0 => $lineItemPrice]);

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);

        self::assertSame('15', $regularLineItem->getPrice()->getValue());
        self::assertSame('USD', $regularLineItem->getPrice()->getCurrency());

        self::assertEquals(0, $freeFormLineItem->getPrice()->getValue());
        self::assertSame('USD', $freeFormLineItem->getPrice()->getCurrency());
    }
}
