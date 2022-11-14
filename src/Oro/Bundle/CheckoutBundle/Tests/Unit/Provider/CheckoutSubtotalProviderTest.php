<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactory;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutSubtotalProviderTest extends AbstractSubtotalProviderTest
{
    use EntityTrait;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var CombinedPriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTreeHandler;

    /** @var CheckoutSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($value) {
                return round($value);
            });

        $this->provider = new CheckoutSubtotalProvider(
            $this->translator,
            $roundingService,
            $this->productPriceProvider,
            $this->priceListTreeHandler,
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
            new ProductPriceScopeCriteriaFactory()
        );
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature('oro_price_lists_combined');
    }

    public function testGetSubtotalWithoutLineItems()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(CheckoutSubtotalProvider::LABEL)
            ->willReturn('test');

        $entity = new Checkout();
        $entity->setCurrency('USD');

        $subtotal = $this->provider->getSubtotal($entity);
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($entity->getCurrency(), $subtotal->getCurrency());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals(0, $subtotal->getAmount());
        $this->assertFalse($subtotal->isVisible());
    }

    /**
     * @dataProvider getPriceDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testGetSubtotalByCurrencyWithEnabledPriceListFeature(
        float $value,
        string $identifier,
        float $defaultQuantity,
        float $quantity,
        int $precision,
        string $code,
        float $expectedValue,
        string $expectedSubtotalCurrency,
        ?string $subtotalCurrency = null,
        ?string $entityCurrency = null
    ) {
        $customer = new Customer();
        $website = new Website();
        $defaultCurrency = 'USD';

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(CheckoutSubtotalProvider::LABEL)
            ->willReturn('test');

        $product = $this->getProduct();
        $productUnit = $this->getProductUnit($code, $precision);
        $this->expectsGetMatchedPrices($value, $identifier, $defaultQuantity);

        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity);

        $entity = new Checkout();
        $entity
            ->setCustomer($customer)
            ->setWebsite($website)
            ->addLineItem($lineItem)
            ->setCurrency($entityCurrency ?: $defaultCurrency);

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->priceListTreeHandler->expects($this->exactly($entity->getLineItems()->count()))
            ->method('getPriceList')
            ->with($entity->getCustomer(), $entity->getWebsite())
            ->willReturn($priceList);

        $subtotal = $subtotalCurrency
            ? $this->provider->getSubtotalByCurrency($entity, $subtotalCurrency)
            : $this->provider->getSubtotal($entity);

        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($expectedSubtotalCurrency, $subtotal->getCurrency());
        $this->assertSame(1, $subtotal->getPriceList()->getId());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals($expectedValue, $subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    /**
     * @dataProvider getPriceDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testGetSubtotalByCurrencyWithDisabledPriceListFeature(
        float $value,
        string $identifier,
        float $defaultQuantity,
        float $quantity,
        int $precision,
        string $code,
        float $expectedValue,
        string $expectedSubtotalCurrency,
        ?string $subtotalCurrency = null,
        ?string $entityCurrency = null
    ) {
        $defaultCurrency = 'USD';

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(CheckoutSubtotalProvider::LABEL)
            ->willReturn('test');

        $product = $this->getProduct();
        $productUnit = $this->getProductUnit($code, $precision);
        $this->expectsGetMatchedPrices($value, $identifier, $defaultQuantity);

        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity);

        $entity = new Checkout();
        $entity->addLineItem($lineItem)
            ->setCurrency($entityCurrency ?: $defaultCurrency);

        $this->priceListTreeHandler->expects($this->never())
            ->method('getPriceList');

        $subtotal = $subtotalCurrency
            ? $this->provider->getSubtotalByCurrency($entity, $subtotalCurrency)
            : $this->provider->getSubtotal($entity);

        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($expectedSubtotalCurrency, $subtotal->getCurrency());
        $this->assertNull($subtotal->getPriceList());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals($expectedValue, $subtotal->getAmount());
        $this->assertTrue($subtotal->isVisible());
    }

    /**
     * @dataProvider getPriceDataProviderWithFixedPrice
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testGetSubtotalByCurrencyWithFixedPriceLineItem(
        float $value,
        string $identifier,
        float $defaultQuantity,
        float $quantity,
        int $precision,
        string $code,
        float $expectedValue,
        Price $lineItemPrice,
        string $expectedSubtotalCurrency,
        ?string $entityCurrency = null,
        ?string $subtotalCurrency = null
    ) {
        $defaultCurrency = 'USD';

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(CheckoutSubtotalProvider::LABEL)
            ->willReturn('test');

        $product = $this->getProduct();
        $productUnit = $this->getProductUnit($code, $precision);
        $this->expectsGetMatchedPrices($value, $identifier, $defaultQuantity);

        $lineItem = new CheckoutLineItem();
        $lineItem->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPriceFixed(true)
            ->setPrice($lineItemPrice);

        $entity = new Checkout();
        $entity->addLineItem($lineItem)
            ->setCurrency($entityCurrency ?: $defaultCurrency);

        $this->priceListTreeHandler->expects($this->never())
            ->method('getPriceList');

        $subtotal = $subtotalCurrency
            ? $this->provider->getSubtotalByCurrency($entity, $subtotalCurrency)
            : $this->provider->getSubtotal($entity);

        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(CheckoutSubtotalProvider::TYPE, $subtotal->getType());
        $this->assertEquals('test', $subtotal->getLabel());
        $this->assertEquals($expectedSubtotalCurrency, $subtotal->getCurrency());
        $this->assertNull($subtotal->getPriceList());
        $this->assertIsFloat($subtotal->getAmount());
        $this->assertEquals($expectedValue, $subtotal->getAmount());
        $this->assertFalse($subtotal->isVisible());
    }

    public function testIsSupported()
    {
        $entity = new Checkout();
        $this->assertTrue($this->provider->isSupported($entity));
    }

    public function testIsNotSupported()
    {
        $entity = new \stdClass();
        $this->assertFalse($this->provider->isSupported($entity));
    }

    private function getProductUnit(string $code, int $precision): ProductUnit
    {
        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->any())
            ->method('getCode')
            ->willReturn($code);
        $productUnit->expects($this->any())
            ->method('getDefaultPrecision')
            ->willReturn($precision);

        return $productUnit;
    }

    private function getProduct(): Product
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        return $product;
    }

    private function expectsGetMatchedPrices($value, $identifier, $defaultQuantity): void
    {
        $price = $this->createMock(Price::class);
        $price->expects($this->any())
            ->method('getValue')
            ->willReturn($value / $defaultQuantity);

        $this->productPriceProvider->expects($this->any())
            ->method('getMatchedPrices')
            ->willReturn([$identifier => $price]);
    }

    public function getPriceDataProvider(): array
    {
        return [
            'kilogram' => [
                'value' => 25.2,
                'identifier' => '1-kg-3-USD',
                'defaultQuantity' => 0.5,
                'quantity' => 3,
                'precision' => 0,
                'code' => 'kg',
                'expectedValue' => 151.0,
                'expectedSubtotalCurrency' => 'USD',
            ],
            'by currency' => [
                'value' => 142.0,
                'identifier' => '1-item-2-EUR',
                'defaultQuantity' => 1,
                'quantity' => 2,
                'precision' => 0,
                'code' => 'item',
                'expectedValue' => 284,
                'expectedSubtotalCurrency' => 'EUR',
                'subtotalCurrency' => 'EUR',
            ],
            'by entity currency' => [
                'value' => 142.0,
                'identifier' => '1-item-2-EUR',
                'defaultQuantity' => 1,
                'quantity' => 2,
                'precision' => 0,
                'code' => 'item',
                'expectedValue' => 284,
                'expectedSubtotalCurrency' => 'EUR',
                'subtotalCurrency' => null,
                'entityCurrency' => 'EUR'
            ]
        ];
    }

    public function getPriceDataProviderWithFixedPrice(): array
    {
        $kgLineItemPrice = new Price();
        $kgLineItemPrice->setValue(25.2);
        $kgLineItemPrice->setCurrency('USD');

        $itemLineItemPrice = new Price();
        $itemLineItemPrice->setValue(142.0);
        $itemLineItemPrice->setCurrency('USD');

        return [
            'kilogram' => [
                'value' => 25.2,
                'identifier' => '1-kg-3-USD',
                'defaultQuantity' => 0.5,
                'quantity' => 3,
                'precision' => 0,
                'code' => 'kg',
                'expectedValue' => 76,
                'lineItemPrice' => $kgLineItemPrice,
                'expectedSubtotalCurrency' => 'USD',
            ],
            'by currency' => [
                'value' => 142.0,
                'identifier' => '1-item-2-EUR',
                'defaultQuantity' => 1,
                'quantity' => 2,
                'precision' => 0,
                'code' => 'item',
                'expectedValue' => 284,
                'lineItemPrice' => $itemLineItemPrice,
                'expectedSubtotalCurrency' => 'EUR',
                'subtotalCurrency' => 'EUR'
            ],
            'by entity currency' => [
                'value' => 142.0,
                'identifier' => '1-item-2-EUR',
                'defaultQuantity' => 1,
                'quantity' => 2,
                'precision' => 0,
                'code' => 'item',
                'expectedValue' => 284,
                'lineItemPrice' => $itemLineItemPrice,
                'expectedSubtotalCurrency' => 'EUR',
                'subtotalCurrency' => null,
                'entityCurrency' => 'EUR'
            ]
        ];
    }
}
