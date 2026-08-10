<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener\DraftSession;

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
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\DraftSession\SetMatchedPricesOnOrderDraftCreatedEventListener;
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

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotShoppingList(): void
    {
        $event = new EntityDraftCreatedEvent(
            $this->createMock(EntityDraftAwareInterface::class),
            new Order()
        );

        $this->priceScopeCriteriaFactory->expects(self::never())
            ->method('createByContext');
        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $event = new EntityDraftCreatedEvent(
            new ShoppingList(),
            $this->createMock(EntityDraftAwareInterface::class)
        );

        $this->priceScopeCriteriaFactory->expects(self::never())
            ->method('createByContext');
        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedWhenNoLineItemsWithoutPrices(): void
    {
        $orderDraft = new Order();

        $lineItemWithoutProduct = new OrderLineItem();
        $orderDraft->addLineItem($lineItemWithoutProduct);

        $lineItemWithPrice = new OrderLineItem();
        $lineItemWithPrice->setProduct(new Product());
        $lineItemWithPrice->setPrice(Price::create('10', 'USD'));
        $orderDraft->addLineItem($lineItemWithPrice);

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->priceScopeCriteriaFactory->expects(self::never())
            ->method('createByContext');
        $this->productLineItemPriceProvider->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->assertLoggerNotCalled();

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedAppliesMatchedPrice(): void
    {
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct(new Product());
        $orderDraft->addLineItem($lineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $lineItemPrice = new ProductLineItemPrice($lineItem, Price::create('25', 'USD'), 25.0);
        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([0 => $lineItem], $scopeCriteria, 'USD')
            ->willReturn([0 => $lineItemPrice]);

        $this->assertLoggerNotCalled();

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertNotNull($lineItem->getPrice());
        self::assertSame('25', $lineItem->getPrice()->getValue());
        self::assertSame('USD', $lineItem->getPrice()->getCurrency());
    }

    public function testOnEntityDraftCreatedSkipsLineItemWithoutMatchedPrice(): void
    {
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct(new Product());
        $orderDraft->addLineItem($lineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->willReturn([]);

        $this->assertLoggerNotCalled();

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertNull($lineItem->getPrice());
    }

    public function testOnEntityDraftCreatedAppliesKitItemPrices(): void
    {
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $kitItemWithPrice = new ProductKitItem();
        ReflectionUtil::setId($kitItemWithPrice, 5);
        $kitItemLineItemWithPrice = new OrderProductKitItemLineItem();
        // Set kitItemId before kitItem to avoid calling getDefaultLabel() (extended field)
        // in updateKitItemFallbackFields().
        $kitItemLineItemWithPrice->setKitItemId(5);
        $kitItemLineItemWithPrice->setKitItem($kitItemWithPrice);

        $kitItemWithoutPrice = new ProductKitItem();
        ReflectionUtil::setId($kitItemWithoutPrice, 6);
        $kitItemLineItemWithoutPrice = new OrderProductKitItemLineItem();
        $kitItemLineItemWithoutPrice->setKitItemId(6);
        $kitItemLineItemWithoutPrice->setKitItem($kitItemWithoutPrice);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct(new Product());
        $lineItem->addKitItemLineItem($kitItemLineItemWithPrice);
        $lineItem->addKitItemLineItem($kitItemLineItemWithoutPrice);
        $orderDraft->addLineItem($lineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $kitLineItemPrice = new ProductKitLineItemPrice($lineItem, Price::create('100', 'USD'), 100.0);
        $kitLineItemPrice->addKitItemLineItemPrice(
            new ProductKitItemLineItemPrice($kitItemLineItemWithPrice, Price::create('20', 'USD'), 20.0)
        );

        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([0 => $lineItem], $scopeCriteria, 'USD')
            ->willReturn([0 => $kitLineItemPrice]);

        $this->assertLoggerNotCalled();

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertNotNull($lineItem->getPrice());
        self::assertSame('100', $lineItem->getPrice()->getValue());
        self::assertNotNull($kitItemLineItemWithPrice->getPrice());
        self::assertSame('20', $kitItemLineItemWithPrice->getPrice()->getValue());
        self::assertNull($kitItemLineItemWithoutPrice->getPrice());
    }

    public function testOnEntityDraftCreatedLogsErrorWhenProviderThrows(): void
    {
        $orderDraft = new Order();
        $orderDraft->setCurrency('USD');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct(new Product());
        $orderDraft->addLineItem($lineItem);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($orderDraft)
            ->willReturn($scopeCriteria);

        $exception = new \RuntimeException('Pricing failure');
        $this->productLineItemPriceProvider->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Failed to fetch matched prices for order draft line items.',
                ['exception' => $exception]
            );

        $event = new EntityDraftCreatedEvent(new ShoppingList(), $orderDraft);

        $this->listener->onEntityDraftCreated($event);

        self::assertNull($lineItem->getPrice());
    }
}
