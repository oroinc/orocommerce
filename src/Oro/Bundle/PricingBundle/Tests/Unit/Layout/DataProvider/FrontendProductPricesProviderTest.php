<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\FrontendProductUnitsProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;

class FrontendProductPricesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userCurrencyManager;

    /** @var ProductPriceFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceFormatter;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productVariantAvailabilityProvider;

    /** @var ProductPriceScopeCriteriaRequestHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeCriteriaRequestHandler;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var FrontendProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitsProvider;

    /** @var FrontendProductPricesProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->productPriceFormatter = $this->createMock(ProductPriceFormatter::class);
        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->productUnitsProvider = $this->createMock(FrontendProductUnitsProvider::class);

        $this->provider = new FrontendProductPricesProvider(
            $this->scopeCriteriaRequestHandler,
            $this->productVariantAvailabilityProvider,
            $this->userCurrencyManager,
            $this->productPriceFormatter,
            $this->productPriceProvider,
            $this->productUnitsProvider
        );
    }

    /**
     * @dataProvider getByProductSimpleDataProvider
     */
    public function testGetByProductSimple(Product|ProductView $simpleProduct)
    {
        $prices = [
            42 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices([$simpleProduct], [], $prices);

        $this->assertEquals(
            [$this->getFormattedPrice(10, 'USD', 1, 'each')],
            $this->provider->getByProduct($simpleProduct)
        );
    }

    public function getByProductSimpleDataProvider(): array
    {
        return [
            [$this->getProduct(42, Product::TYPE_SIMPLE)],
            [$this->getProductView(42, Product::TYPE_SIMPLE)]
        ];
    }

    /**
     * @dataProvider getByProductConfigurableDataProvider
     */
    public function testGetByProductConfigurable(Product|ProductView $configurableProduct)
    {
        $variantProduct101 = $this->getProduct(101, Product::TYPE_SIMPLE);
        $variantProduct102 = $this->getProduct(102, Product::TYPE_SIMPLE);

        $prices = [
            100 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
            101 => $this->getPricesArray(5, 1, 'USD', ['each', 'set']),
            102 => $this->getPricesArray(6, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices(
            [$configurableProduct, $variantProduct101, $variantProduct102],
            [100 => [101, 102]],
            $prices
        );

        $this->assertEquals(
            [$this->getFormattedPrice(10, 'USD', 1, 'each')],
            $this->provider->getByProduct($configurableProduct)
        );
    }

    public function getByProductConfigurableDataProvider(): array
    {
        return [
            [$this->getProduct(100, Product::TYPE_CONFIGURABLE)],
            [$this->getProductView(100, Product::TYPE_CONFIGURABLE)]
        ];
    }

    public function testGetVariantsPricesByProductSimple()
    {
        $simpleProduct1 = $this->getProduct(42, Product::TYPE_SIMPLE);

        $prices = [
            42 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices([$simpleProduct1], [], $prices);

        $this->assertEquals([], $this->provider->getVariantsPricesByProduct($simpleProduct1));
    }

    public function testGetVariantsPricesByProductConfigurable()
    {
        $configurableProduct100 = $this->getProduct(100, Product::TYPE_CONFIGURABLE);
        $variantProduct101 = $this->getProduct(101, Product::TYPE_SIMPLE);
        $variantProduct102 = $this->getProduct(102, Product::TYPE_SIMPLE);

        $prices = [
            100 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
            101 => $this->getPricesArray(5, 1, 'USD', ['each', 'set']),
            102 => $this->getPricesArray(6, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices(
            [$configurableProduct100, $variantProduct101, $variantProduct102],
            [100 => [101, 102]],
            $prices
        );

        $this->assertEquals(
            [
                101 => [$this->getFormattedPrice(5, 'USD', 1, 'each')],
                102 => [$this->getFormattedPrice(6, 'USD', 1, 'each')]
            ],
            $this->provider->getVariantsPricesByProduct($configurableProduct100)
        );
    }

    public function testGetByProductsEmptyProducts()
    {
        $this->assertSame([], $this->provider->getByProducts([]));
    }

    public function testGetByProducts()
    {
        $simpleProduct1 = $this->getProductView(1, Product::TYPE_SIMPLE);
        $configurableProduct100 = $this->getProductView(100, Product::TYPE_CONFIGURABLE);
        $variantProduct101 = $this->getProductView(101, Product::TYPE_SIMPLE);
        $variantProduct102 = $this->getProductView(102, Product::TYPE_SIMPLE);

        $prices = [
            1 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
            100 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
            101 => $this->getPricesArray(5, 1, 'USD', ['each', 'set']),
            102 => $this->getPricesArray(6, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices(
            [$simpleProduct1, $configurableProduct100, $variantProduct101, $variantProduct102],
            [100 => [101, 102]],
            $prices
        );

        $this->assertEquals(
            [
                1 => [$this->getFormattedPrice(20, 'USD', 1, 'each')],
                100 => [$this->getFormattedPrice(10, 'USD', 1, 'each')],
                101 => [$this->getFormattedPrice(5, 'USD', 1, 'each')]
            ],
            $this->provider->getByProducts([$simpleProduct1, $configurableProduct100, $variantProduct101])
        );
    }

    public function isPriceBlockVisibleByProductDataProvider(): array
    {
        $configurableProduct1 = $this->getProduct(
            1,
            Product::TYPE_CONFIGURABLE,
            [$this->getUnitPrecision('each', true)]
        );
        $variant101 = $this->getProduct(
            101,
            Product::TYPE_SIMPLE,
            [$this->getUnitPrecision('each', true)]
        );
        $variant102 = $this->getProduct(
            102,
            Product::TYPE_SIMPLE,
            [$this->getUnitPrecision('each', true)]
        );
        $simpleProduct2 = $this->getProduct(
            2,
            Product::TYPE_SIMPLE,
            [$this->getUnitPrecision('each', true)]
        );

        return [
            'configurable product with prices' => [
                'checkedProduct' => $configurableProduct1,
                'products' => [$configurableProduct1, $variant101, $variant102],
                'variants' => [1 => [101, 102]],
                'prices' => [
                    1 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
                    101 => $this->getPricesArray(30, 1, 'USD', ['each', 'set']),
                    102 => $this->getPricesArray(40, 1, 'USD', ['each', 'set']),
                ],
                'expected' => true,
                'expectedNoExternalServiceCalled' => false,
            ],
            'configurable product without prices' => [
                'checkedProduct' => $configurableProduct1,
                'products' => [$configurableProduct1, $variant101, $variant102],
                'variants' => [1 => [101, 102]],
                'prices' => [
                    101 => $this->getPricesArray(30, 1, 'USD', ['each', 'set']),
                    102 => $this->getPricesArray(40, 1, 'USD', ['each', 'set']),
                ],
                'expected' => false,
                'expectedNoExternalServiceCalled' => false,
            ],
            'simple product with prices' => [
                'checkedProduct' => $simpleProduct2,
                'products' => [$simpleProduct2],
                'variants' => [],
                'prices' => [
                    2 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
                ],
                'expected' => true,
                'expectedNoExternalServiceCalled' => true,
            ],
            'simple product without prices' => [
                'checkedProduct' => $simpleProduct2,
                'products' => [$simpleProduct2],
                'variants' => [],
                'prices' => [],
                'expected' => true,
                'expectedNoExternalServiceCalled' => true,
            ],
        ];
    }

    /**
     * @dataProvider isPriceBlockVisibleByProductDataProvider
     */
    public function testIsShowProductPriceContainer(
        Product $checkedProduct,
        array $products,
        array $variants,
        array $prices,
        bool $expected,
        bool $expectedNoExternalServiceCalled
    ) {
        if ($expectedNoExternalServiceCalled) {
            $this->expectNoExternalServiceCalled();
        } else {
            $this->expectProductsAndPrices($products, $variants, $prices);
        }

        $this->assertEquals($expected, $this->provider->isShowProductPriceContainer($checkedProduct));
    }

    private function expectProductsAndPrices(array $products, array $variants, array $prices)
    {
        $currency = 'USD';
        $productIds = [];
        $productUnits = [];
        /** @var Product|ProductView $product */
        foreach ($products as $product) {
            $productIds[] = $product->getId();
            if ($product instanceof ProductView) {
                $productUnits[$product->getId()] = array_keys($product->get('product_units'));
            } else {
                $sellUnits = [];
                foreach ($product->getUnitPrecisions() as $unitPrecision) {
                    if ($unitPrecision->isSell()) {
                        $sellUnits[] = $unitPrecision->getUnit()->getCode();
                    }
                }
                $productUnits[$product->getId()] = $sellUnits;
            }
        }

        $this->userCurrencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->scopeCriteriaRequestHandler->expects($this->any())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->productPriceProvider->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($scopeCriteria, $productIds, [$currency])
            ->willReturn($prices);

        $this->productUnitsProvider->expects($this->once())
            ->method('getUnitsForProducts')
            ->with($productIds)
            ->willReturn($productUnits);

        $this->productVariantAvailabilityProvider->expects($this->any())
            ->method('getSimpleProductIdsGroupedByConfigurable')
            ->willReturn($variants);

        $this->productPriceFormatter->expects($this->any())
            ->method('formatProducts')
            ->willReturnCallback(function ($productsPrices) {
                $formattedProductsPrices = [];
                foreach ($productsPrices as $productId => $productsPrice) {
                    foreach ($productsPrice as $unit => $unitPrices) {
                        /** @var ProductPriceDTO $unitPrice */
                        foreach ($unitPrices as $unitPrice) {
                            $priceValue = $unitPrice->getPrice()->getValue();
                            $priceCurrency = $unitPrice->getPrice()->getCurrency();
                            $qty = $unitPrice->getQuantity();
                            $unitCode = $unitPrice->getUnit()->getCode();

                            $formattedProductsPrices[$productId][sprintf(
                                '%s_%s',
                                $unit,
                                $unitPrice->getQuantity()
                            )] = $this->getFormattedPrice($priceValue, $priceCurrency, $qty, $unitCode);
                        }
                    }
                }

                return $formattedProductsPrices;
            });
    }

    private function expectNoExternalServiceCalled()
    {
        $this->userCurrencyManager->expects($this->never())
            ->method('getUserCurrency');
        $this->productPriceProvider->expects($this->never())
            ->method('getPricesByScopeCriteriaAndProducts');
        $this->productUnitsProvider->expects($this->never())
            ->method('getUnitsForProducts');
        $this->productVariantAvailabilityProvider->expects($this->never())
            ->method('getSimpleProductIdsGroupedByConfigurable');
        $this->productPriceFormatter->expects($this->never())
            ->method('formatProducts');
    }

    private function getPricesArray(float $price, int $quantity, string $currency, array $unitCodes): array
    {
        return array_map(function ($unitCode) use ($price, $quantity, $currency) {
            return $this->getPrice($price, $currency, $quantity, $unitCode);
        }, $unitCodes);
    }

    private function getPrice(float $price, string $currency, int $quantity, string $unitCode): ProductPriceDTO
    {
        return new ProductPriceDTO(
            $this->getProduct(1, Product::TYPE_SIMPLE, []),
            Price::create($price, $currency),
            $quantity,
            $this->getUnit($unitCode)
        );
    }

    private function getFormattedPrice(float $priceValue, string $priceCurrency, float $qty, string $unitCode): array
    {
        return [
            'price' => $priceValue,
            'currency' => $priceCurrency,
            'quantity' => $qty,
            'unit' => $unitCode,
            'formatted_price' => $priceValue . ' ' . $priceCurrency,
            'formatted_unit' => $unitCode . ' FORMATTED',
            'quantity_with_unit' => $qty . ' ' . $unitCode
        ];
    }

    private function getProductView(int $id, string $type, array $units = null): ProductView
    {
        $product = new ProductView();
        $product->set('id', $id);
        $product->set('type', $type);
        $product->set('product_units', null === $units ? ['each' => 0] : array_fill_keys($units, 0));

        return $product;
    }

    private function getProduct(int $id, string $type, array $unitPrecisions = null): ProductStub
    {
        $product = new ProductStub();
        $product->setId($id);
        $product->setType($type);
        if (null === $unitPrecisions) {
            $unitPrecisions = [
                $this->getUnitPrecision('each', true),
                $this->getUnitPrecision('set', false)
            ];
        }
        foreach ($unitPrecisions as $unitPrecision) {
            $product->addUnitPrecision($unitPrecision);
        }

        return $product;
    }

    private function getUnitPrecision(string $unitCode, bool $sell): ProductUnitPrecision
    {
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setSell($sell);
        $productUnitPrecision->setUnit($this->getUnit($unitCode));

        return $productUnitPrecision;
    }

    private function getUnit(string $unitCode): ProductUnit
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        return $unit;
    }
}
