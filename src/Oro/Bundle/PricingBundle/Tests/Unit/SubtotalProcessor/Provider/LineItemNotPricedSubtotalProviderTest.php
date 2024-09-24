<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityNotPricedStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\LineItemNotPricedStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
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

    private ProductLineItemPriceProviderInterface|MockObject $productLineItemsPriceProvider;

    #[\Override]
    protected function setUp(): void
    {
        $currencyManager = $this->createMock(UserCurrencyManager::class);
        $websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->productLineItemsPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);

        $this->provider = new LineItemNotPricedSubtotalProvider(
            new SubtotalProviderConstructorArguments($currencyManager, $websiteCurrencyProvider),
            $translator,
            $this->productLineItemsPriceProvider
        );

        $translator
            ->method('trans')
            ->with(LineItemNotPricedSubtotalProvider::LABEL)
            ->willReturn('test');

        $websiteCurrencyProvider
            ->method('getWebsiteDefaultCurrency')
            ->with(self::WEBSITE_ID)
            ->willReturn(self::CURRENCY_USD);
    }

    public function testGetSubtotal(): void
    {
        $entity = new EntityNotPricedStub();
        $entity->setWebsite(new WebsiteStub(self::WEBSITE_ID));
        $lineItem1 = $this->createLineItem(1, 3, 'kg');
        $lineItem2 = $this->createLineItem(2, 7, 'item');
        $entity
            ->addLineItem($lineItem1)
            ->addLineItem($lineItem2);

        $lineItem1Price = new ProductLineItemPrice($lineItem1, Price::create(10, self::CURRENCY_USD), 100);
        $lineItem2Price = new ProductLineItemPrice($lineItem2, Price::create(20, self::CURRENCY_USD), 200);

        $this->productLineItemsPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPricesForLineItemsHolder')
            ->with($entity, self::CURRENCY_USD)
            ->willReturn([$lineItem1Price, $lineItem2Price]);

        $subtotal = $this->provider->getSubtotal($entity);

        self::assertInstanceOf(Subtotal::class, $subtotal);
        self::assertEquals(LineItemNotPricedSubtotalProvider::TYPE, $subtotal->getType());
        self::assertEquals('test', $subtotal->getLabel());
        self::assertEquals(self::CURRENCY_USD, $subtotal->getCurrency());
        self::assertIsFloat($subtotal->getAmount());
        self::assertSame(300.0, $subtotal->getAmount());
        self::assertTrue($subtotal->isVisible());
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
