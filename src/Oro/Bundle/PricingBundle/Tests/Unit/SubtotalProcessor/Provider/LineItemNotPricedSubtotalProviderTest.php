<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
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

    private ProductPriceProviderInterface|MockObject $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    protected function setUp(): void
    {
        $currencyManager = $this->createMock(UserCurrencyManager::class);
        $websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);

        $this->provider = new LineItemNotPricedSubtotalProvider(
            $translator,
            $roundingService,
            $this->productPriceProvider,
            new SubtotalProviderConstructorArguments($currencyManager, $websiteCurrencyProvider),
            $this->priceScopeCriteriaFactory
        );

        $translator
            ->method('trans')
            ->with(LineItemNotPricedSubtotalProvider::LABEL)
            ->willReturn('test');

        $roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value) => round($value, 2));

        $websiteCurrencyProvider
            ->method('getWebsiteDefaultCurrency')
            ->with(self::WEBSITE_ID)
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
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($entity)
            ->willReturn($searchScope);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getMatchedPrices')
            ->with(
                [
                    spl_object_hash($lineItem1) => $this->createProductPriceCriteria($lineItem1),
                    spl_object_hash($lineItem2) => $this->createProductPriceCriteria($lineItem2),
                ],
                $searchScope
            )
            ->willReturn($prices);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals(self::CURRENCY_USD, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(7.25, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
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
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($entity)
            ->willReturn($searchScope);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getMatchedPrices')
            ->with(
                [
                    spl_object_hash($lineItem1) => $this->createProductPriceCriteria($lineItem1),
                    spl_object_hash($lineItem2) => $this->createProductPriceCriteria($lineItem2),
                    spl_object_hash($productKitLineItem) => $this->createProductPriceCriteria($productKitLineItem),
                    spl_object_hash($kitItemLineItem1) => $this->createProductPriceCriteria($kitItemLineItem1),
                    spl_object_hash($kitItemLineItem2) => $this->createProductPriceCriteria($kitItemLineItem2),
                ],
                $searchScope
            )
            ->willReturn($prices);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals(self::CURRENCY_USD, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(85.81, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertEquals([
            spl_object_hash($lineItem1) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 0.03,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 0.09,
            ],
            spl_object_hash($lineItem2) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 1.02,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 7.14,
            ],
            spl_object_hash($productKitLineItem) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 39.288,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 78.576,
            ],
            spl_object_hash($kitItemLineItem1) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 10.123,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 20.246,
            ],
            spl_object_hash($kitItemLineItem2) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 5.345,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 16.035,
            ],
        ], $subtotal->getData());
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
        $this->priceScopeCriteriaFactory->expects(self::once())
            ->method('createByContext')
            ->with($entity)
            ->willReturn($searchScope);

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getMatchedPrices')
            ->with(
                [
                    spl_object_hash($lineItem1) => $this->createProductPriceCriteria($lineItem1),
                    spl_object_hash($productKitLineItem) => $this->createProductPriceCriteria($productKitLineItem),
                    spl_object_hash($kitItemLineItem1) => $this->createProductPriceCriteria($kitItemLineItem1),
                    spl_object_hash($kitItemLineItem2) => $this->createProductPriceCriteria($kitItemLineItem2),
                ],
                $searchScope
            )
            ->willReturn($prices);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals(self::CURRENCY_USD, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(72.67, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
        self::assertEquals([
            spl_object_hash($lineItem1) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 0.03,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 0.09,
            ],
            spl_object_hash($productKitLineItem) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 36.29,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 72.58,
            ],
            spl_object_hash($kitItemLineItem1) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 10.123,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 20.246,
            ],
            spl_object_hash($kitItemLineItem2) => [
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_PRICE => 5.345,
                LineItemNotPricedSubtotalProvider::EXTRA_DATA_SUBTOTAL => 16.035,
            ],
        ], $subtotal->getData());
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

        $subtotal = $this->provider->getSubtotal($entity);
        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertEquals(0, $subtotal->getAmount());
        self::assertFalse($subtotal->isVisible());
        self::assertEquals([], $subtotal->getData());
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
