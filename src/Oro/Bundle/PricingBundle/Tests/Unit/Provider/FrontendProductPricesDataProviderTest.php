<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class FrontendProductPricesDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const TEST_CURRENCY = 'USD';

    private ProductPriceProviderInterface|MockObject $productPriceProvider;

    private FrontendProductPricesDataProvider $provider;

    private ProductPriceScopeCriteriaInterface|MockObject $scopeCriteria;

    private ProductPriceCriteriaFactoryInterface|MockObject $productPriceCriteriaFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);

        $this->provider = new FrontendProductPricesDataProvider(
            $this->productPriceProvider,
            $userCurrencyManager,
            $scopeCriteriaRequestHandler,
            $this->productPriceCriteriaFactory
        );

        $userCurrencyManager
            ->method('getUserCurrency')
            ->willReturn(self::TEST_CURRENCY);

        $this->scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $scopeCriteriaRequestHandler
            ->method('getPriceScopeCriteria')
            ->willReturn($this->scopeCriteria);
    }

    public function testGetProductsPricesWhenProductsPricesCriteriaNotCreatable(): void
    {
        $lineItem = $this->createMock(ProductLineItem::class);

        $this->productPriceProvider->expects(self::once())
            ->method('getMatchedPrices')
            ->with([], $this->scopeCriteria)
            ->willReturn([]);

        $this->productPriceCriteriaFactory->method('createListFromProductLineItems')->willReturn([]);

        $result = $this->provider->getProductsMatchedPrice([$lineItem]);

        self::assertEmpty($result);
    }

    public function testGetProductsPricesWhenProductsPricesCriteriaIsCreatable(): void
    {
        $lineItem = $this->createMock(ProductLineItem::class);
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);

        $this->productPriceCriteriaFactory
            ->expects($this->once())
            ->method('createListFromProductLineItems')
            ->with([$lineItem])
            ->willReturn([$productPriceCriteria]);

        $this->productPriceProvider->expects(self::once())
            ->method('getMatchedPrices')
            ->with($this->equalTo([$productPriceCriteria]), $this->scopeCriteria)
            ->willReturn([
                '42-test-100-USD' => 123,
            ]);

        $result = $this->provider->getProductsMatchedPrice([$lineItem]);

        self::assertEquals([
            '42' => [
                'test' => 123
            ]
        ], $result);
    }

    /**
     * @dataProvider getAllPricesForLineItemsProvider
     */
    public function testGetAllPricesForLineItems(
        array $lineItems,
        array $products,
        array $prices,
        array $expectedPrices
    ): void {
        $this->productPriceProvider
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($this->scopeCriteria, $products, [self::TEST_CURRENCY])
            ->willReturn($prices);

        $result = $this->provider->getAllPricesForLineItems($lineItems);
        self::assertEquals($expectedPrices, $result);
    }

    public function getAllPricesForLineItemsProvider(): array
    {
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $quantity = 100;
        $priceValue = 10;

        $lineItemWithProduct = new ProductLineItem('test');
        $lineItemWithProduct->setProduct($product);
        $lineItemWithProduct->setUnit($productUnit);
        $lineItemWithProduct->setQuantity($quantity);

        $lineItemWOProduct = new ProductLineItem('test');
        $lineItemWOProduct->setUnit($productUnit);
        $lineItemWOProduct->setQuantity($quantity);

        return [
            'line item with product' => [
                'lineItems' => [$lineItemWithProduct],
                'products' => [$product],
                'prices' => [
                    42 => $this->getPricesArray($priceValue, $quantity, self::TEST_CURRENCY, ['item']),
                ],
                'expectedPrices' => [
                    42 => [
                        'item' => [$this->createPrice($priceValue, self::TEST_CURRENCY, $quantity, 'item')],
                    ],
                ],
            ],
            'line item without product' => [
                'lineItems' => [$lineItemWOProduct],
                'products' => [],
                'prices' => [],
                'expectedPrices' => [],
            ],
        ];
    }

    /**
     * @dataProvider getAllPricesForProductsProvider
     */
    public function testGetAllPricesForProducts(array $products, array $prices, array $expectedPrices): void
    {
        $this->productPriceProvider
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($this->scopeCriteria, $products, [self::TEST_CURRENCY])
            ->willReturn($prices);

        $result = $this->provider->getAllPricesForProducts($products);
        self::assertEquals($expectedPrices, $result);
    }

    public function getAllPricesForProductsProvider(): array
    {
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $quantity = 100;
        $priceValue = 10;

        return [
            'line item with product' => [
                'products' => [$product],
                'prices' => [
                    42 => $this->getPricesArray($priceValue, $quantity, self::TEST_CURRENCY, ['item']),
                ],
                'expectedPrices' => [
                    42 => [
                        'item' => [$this->createPrice($priceValue, self::TEST_CURRENCY, $quantity, 'item')],
                    ],
                ],
            ],
            'line item without product' => [
                'products' => [],
                'prices' => [],
                'expectedPrices' => [],
            ],
        ];
    }

    private function getPricesArray(float $price, int $quantity, string $currency, array $unitCodes): array
    {
        return array_map(function ($unitCode) use ($price, $quantity, $currency) {
            return $this->createPrice($price, $currency, $quantity, $unitCode);
        }, $unitCodes);
    }

    private function createPrice(float $price, string $currency, int $quantity, string $unitCode): ProductPriceDTO
    {
        return new ProductPriceDTO(
            $this->getEntity(Product::class, ['id' => 1]),
            Price::create($price, $currency),
            $quantity,
            $this->getEntity(ProductUnit::class, ['code' => $unitCode])
        );
    }
}
