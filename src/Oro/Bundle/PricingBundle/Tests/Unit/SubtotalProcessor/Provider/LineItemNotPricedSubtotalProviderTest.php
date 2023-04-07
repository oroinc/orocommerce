<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderCurrencyProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderPricesProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityNotPricedStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\LineItemNotPricedStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\WebsiteBundle\Tests\Unit\Stub\WebsiteStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class LineItemNotPricedSubtotalProviderTest extends TestCase
{
    use EntityTrait;

    public const CURRENCY_USD = 'USD';
    public const WEBSITE_ID = 101;

    private LineItemNotPricedSubtotalProvider $provider;

    private ProductLineItemsHolderPricesProvider|MockObject $productLineItemsHolderPricesProvider;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $productLineItemsHolderCurrencyProvider = $this->createMock(
            ProductLineItemsHolderCurrencyProvider::class
        );
        $this->productLineItemsHolderPricesProvider = $this->createMock(ProductLineItemsHolderPricesProvider::class);

        $this->provider = new LineItemNotPricedSubtotalProvider(
            $translator,
            $roundingService,
            $productLineItemsHolderCurrencyProvider,
            $this->productLineItemsHolderPricesProvider
        );

        $translator
            ->method('trans')
            ->with(LineItemNotPricedSubtotalProvider::LABEL)
            ->willReturn('test');

        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $productLineItemsHolderCurrencyProvider
            ->method('getCurrencyForLineItemsHolder')
            ->willReturn(self::CURRENCY_USD);
    }

    public function testGetSubtotal(): void
    {
        $prices = [];
        $entity = new EntityNotPricedStub();
        $entity->setWebsite(new WebsiteStub(self::WEBSITE_ID));
        $lineItem1 = $this->createLineItem(1, 3, 'kg');
        $lineItem2 = $this->createLineItem(2, 7, 'item');
        $entity
            ->addLineItem($lineItem1)
            ->addLineItem($lineItem2);

        $prices['1-kg-3-USD'] = Price::create(0.033, self::CURRENCY_USD);
        $prices['2-item-7-USD'] = Price::create(1.021, self::CURRENCY_USD);

        $searchScope = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->productLineItemsHolderPricesProvider
            ->expects(self::once())
            ->method('getMatchedPricesForLineItemsHolder')
            ->with($entity)
            ->willReturn(
                [
                    $prices,
                    [
                        spl_object_hash($lineItem1) => $this->createProductPriceCriteria($lineItem1),
                        spl_object_hash($lineItem2) => $this->createProductPriceCriteria($lineItem2),
                    ],
                    $searchScope,
                ]
            );

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals(self::CURRENCY_USD, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(7.25, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertEquals($this->createLineItemSubtotal(0.099, 0.033, 3), $subtotal->getLineItemSubtotal($lineItem1));
        self::assertEquals($this->createLineItemSubtotal(7.147, 1.021, 7), $subtotal->getLineItemSubtotal($lineItem2));
    }

    public function testGetSubtotalWhenHasProductKitItemLineItems(): void
    {
        $prices = [];
        $entity = new EntityNotPricedStub();
        $entity->setWebsite(new WebsiteStub(self::WEBSITE_ID));
        $lineItem1 = $this->createLineItem(1, 3, 'kg');
        $lineItem2 = $this->createLineItem(2, 7, 'item');
        $productKitLineItem = $this->createLineItem(3, 2, 'each');
        $kitItemLineItem1 = $this->createKitItemLineItem(10, 2, 'item');
        $kitItemLineItem2 = $this->createKitItemLineItem(20, 3, 'item');
        $productKitLineItem
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $entity
            ->addLineItem($lineItem1)
            ->addLineItem($lineItem2)
            ->addLineItem($productKitLineItem);

        $prices['1-kg-3-USD'] = Price::create(0.03, self::CURRENCY_USD);
        $prices['2-item-7-USD'] = Price::create(1.02, self::CURRENCY_USD);
        $prices['3-each-2-USD'] = Price::create(2.998, self::CURRENCY_USD);
        $prices['10-item-2-USD'] = Price::create(10.123, self::CURRENCY_USD);
        $prices['20-item-3-USD'] = Price::create(5.345, self::CURRENCY_USD);

        $searchScope = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->productLineItemsHolderPricesProvider
            ->expects(self::once())
            ->method('getMatchedPricesForLineItemsHolder')
            ->with($entity)
            ->willReturn(
                [
                    $prices,
                    [
                        spl_object_hash($lineItem1) => $this->createProductPriceCriteria($lineItem1),
                        spl_object_hash($lineItem2) => $this->createProductPriceCriteria($lineItem2),
                        spl_object_hash($productKitLineItem) => $this->createProductPriceCriteria($productKitLineItem),
                        spl_object_hash($kitItemLineItem1) => $this->createProductPriceCriteria($kitItemLineItem1),
                        spl_object_hash($kitItemLineItem2) => $this->createProductPriceCriteria($kitItemLineItem2),
                    ],
                    $searchScope,
                ]
            );

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals(self::CURRENCY_USD, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(85.79, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertEquals($this->createLineItemSubtotal(0.09, 0.03, 3), $subtotal->getLineItemSubtotal($lineItem1));
        self::assertEquals($this->createLineItemSubtotal(7.14, 1.02, 7), $subtotal->getLineItemSubtotal($lineItem2));
        self::assertEquals(
            $this->createLineItemSubtotal(78.558, 39.279, 2)
                ->addLineItemSubtotal($kitItemLineItem1, $this->createLineItemSubtotal(20.246, 10.123, 2))
                ->addLineItemSubtotal($kitItemLineItem2, $this->createLineItemSubtotal(16.035, 5.345, 3)),
            $subtotal->getLineItemSubtotal($productKitLineItem)
        );
    }

    private function createLineItemSubtotal(?float $amount, ?float $priceValue, ?float $quantity): Subtotal
    {
        return (new Subtotal())
            ->setType(LineItemNotPricedSubtotalProvider::TYPE_LINE_ITEM)
            ->setVisible(false)
            ->setRemovable(false)
            ->setPrice(Price::create($priceValue, self::CURRENCY_USD))
            ->setQuantity($quantity)
            ->setAmount($amount)
            ->setCurrency(self::CURRENCY_USD);
    }

    public function testGetSubtotalWhenNoPriceButHasProductKitItemLineItems(): void
    {
        $prices = [];
        $entity = new EntityNotPricedStub();
        $entity->setWebsite(new WebsiteStub(self::WEBSITE_ID));
        $lineItem1 = $this->createLineItem(1, 3, 'kg');
        $productKitLineItem = $this->createLineItem(3, 2, 'each');
        $kitItemLineItem1 = $this->createKitItemLineItem(10, 2, 'item');
        $kitItemLineItem2 = $this->createKitItemLineItem(20, 3, 'item');
        $productKitLineItem
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $entity
            ->addLineItem($lineItem1)
            ->addLineItem($productKitLineItem);

        $prices['1-kg-3-USD'] = Price::create(0.03, self::CURRENCY_USD);
        $prices['10-item-2-USD'] = Price::create(10.123, self::CURRENCY_USD);
        $prices['20-item-3-USD'] = Price::create(5.345, self::CURRENCY_USD);

        $searchScope = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->productLineItemsHolderPricesProvider
            ->expects(self::once())
            ->method('getMatchedPricesForLineItemsHolder')
            ->with($entity)
            ->willReturn(
                [
                    $prices,
                    [
                        spl_object_hash($lineItem1) => $this->createProductPriceCriteria($lineItem1),
                        spl_object_hash($productKitLineItem) => $this->createProductPriceCriteria($productKitLineItem),
                        spl_object_hash($kitItemLineItem1) => $this->createProductPriceCriteria($kitItemLineItem1),
                        spl_object_hash($kitItemLineItem2) => $this->createProductPriceCriteria($kitItemLineItem2),
                    ],
                    $searchScope,
                ]
            );

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals(self::CURRENCY_USD, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(72.65, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertEquals($this->createLineItemSubtotal(0.09, 0.03, 3), $subtotal->getLineItemSubtotal($lineItem1));
        self::assertEquals(
            $this->createLineItemSubtotal(72.562, 36.281, 2)
                ->addLineItemSubtotal($kitItemLineItem1, $this->createLineItemSubtotal(20.246, 10.123, 2))
                ->addLineItemSubtotal($kitItemLineItem2, $this->createLineItemSubtotal(16.035, 5.345, 3)),
            $subtotal->getLineItemSubtotal($productKitLineItem)
        );
    }

    private function createProductPriceCriteria(ProductLineItemInterface $lineItem): ProductPriceCriteria
    {
        return new ProductPriceCriteria(
            $lineItem->getProduct(),
            $lineItem->getProductUnit(),
            (float)$lineItem->getQuantity(),
            self::CURRENCY_USD
        );
    }

    private function createLineItem(int $productId, float $quantity, string $unitCode): LineItemNotPricedStub
    {
        $product = (new ProductStub())->setId($productId);
        $productUnit = (new ProductUnit())->setCode($unitCode);

        $lineItem = new LineItemNotPricedStub();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity($quantity);

        return $lineItem;
    }

    private function createKitItemLineItem(
        int $productId,
        float $quantity,
        string $unitCode
    ): ProductKitItemLineItemStub {
        $product = (new ProductStub())->setId($productId);
        $productUnit = (new ProductUnit())->setCode($unitCode);

        $kitItemLineItem = new ProductKitItemLineItemStub($productId * 10);
        $kitItemLineItem->setProduct($product);
        $kitItemLineItem->setUnit($productUnit);
        $kitItemLineItem->setQuantity($quantity);

        return $kitItemLineItem;
    }

    public function testGetSubtotalWithoutLineItems(): void
    {
        $entity = new EntityNotPricedStub();
        $searchScope = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $this->productLineItemsHolderPricesProvider
            ->expects(self::once())
            ->method('getMatchedPricesForLineItemsHolder')
            ->with($entity)
            ->willReturn(
                [
                    [],
                    [],
                    $searchScope,
                ]
            );

        $subtotal = $this->provider->getSubtotal($entity);
        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals(self::CURRENCY_USD, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertEquals(0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
    }

    public function testGetSubtotalWithWrongEntity(): void
    {
        self::assertNull($this->provider->getSubtotal(new EntityStub()));
    }

    public function testIsSupported(): void
    {
        $entity = new EntityNotPricedStub();
        self::assertTrue($this->provider->isSupported($entity));
    }

    public function testIsNotSupported(): void
    {
        $entity = new LineItemNotPricedStub();
        self::assertFalse($this->provider->isSupported($entity));
    }
}
