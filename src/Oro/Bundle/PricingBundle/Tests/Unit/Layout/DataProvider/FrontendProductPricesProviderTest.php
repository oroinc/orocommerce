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
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductPricesProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FrontendProductPricesProvider */
    protected $provider;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $userCurrencyManager;

    /** @var ProductPriceFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $productPriceFormatter;

    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productVariantAvailabilityProvider;

    /** @var ProductPriceScopeCriteriaRequestHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeCriteriaRequestHandler;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    protected function setUp(): void
    {
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->productPriceFormatter = $this->createMock(ProductPriceFormatter::class);
        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);

        $this->provider = new FrontendProductPricesProvider(
            $this->scopeCriteriaRequestHandler,
            $this->productVariantAvailabilityProvider,
            $this->userCurrencyManager,
            $this->productPriceFormatter,
            $this->productPriceProvider
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->scopeCriteriaRequestHandler,
            $this->userCurrencyManager,
            $this->productPriceFormatter,
            $this->productVariantAvailabilityProvider,
            $this->productPriceProvider,
            $this->provider
        );
    }

    public function testGetByProductSimple()
    {
        $simpleProduct1 = $this->createProduct(42, Product::TYPE_SIMPLE);

        $prices = [
            42 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices([$simpleProduct1], [], $prices);

        $this->assertEquals(
            ['each_1' => $this->createFormattedPrice(10, 'USD', 1, 'each')],
            $this->provider->getByProduct($simpleProduct1)
        );
    }

    public function testGetVariantsPricesByProductSimple()
    {
        $simpleProduct1 = $this->createProduct(42, Product::TYPE_SIMPLE);

        $prices = [
            42 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices([$simpleProduct1], [], $prices);

        $this->assertEquals([], $this->provider->getVariantsPricesByProduct($simpleProduct1));
    }

    public function testGetByProductConfigurable()
    {
        $configurableProduct100 = $this->createProduct(100, Product::TYPE_CONFIGURABLE);
        $variantProduct101 = $this->createProduct(101, Product::TYPE_SIMPLE);
        $variantProduct102 = $this->createProduct(102, Product::TYPE_SIMPLE);

        $prices = [
            100 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
            101 => $this->getPricesArray(5, 1, 'USD', ['each', 'set']),
            102 => $this->getPricesArray(6, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices(
            [$configurableProduct100, $variantProduct101, $variantProduct102],
            [
                100 => [
                    101 => $variantProduct101,
                    102 => $variantProduct102,
                ]
            ],
            $prices
        );

        $this->assertEquals(
            [
                'each_1' => $this->createFormattedPrice(10, 'USD', 1, 'each')
            ],
            $this->provider->getByProduct($configurableProduct100)
        );
    }

    public function testGetVariantsPricesByProductConfigurable()
    {
        $configurableProduct100 = $this->createProduct(100, Product::TYPE_CONFIGURABLE);
        $variantProduct101 = $this->createProduct(101, Product::TYPE_SIMPLE);
        $variantProduct102 = $this->createProduct(102, Product::TYPE_SIMPLE);

        $prices = [
            100 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
            101 => $this->getPricesArray(5, 1, 'USD', ['each', 'set']),
            102 => $this->getPricesArray(6, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices(
            [$configurableProduct100, $variantProduct101, $variantProduct102],
            [
                100 => [
                    101 => $variantProduct101,
                    102 => $variantProduct102,
                ]
            ],
            $prices
        );

        $this->assertEquals(
            [
                101 => [
                    'each_1' => $this->createFormattedPrice(5, 'USD', 1, 'each')
                ],
                102 => [
                    'each_1' => $this->createFormattedPrice(6, 'USD', 1, 'each')
                ],
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
        $simpleProduct1 = $this->createProduct(1, Product::TYPE_SIMPLE);
        $configurableProduct100 = $this->createProduct(100, Product::TYPE_CONFIGURABLE);
        $variantProduct101 = $this->createProduct(101, Product::TYPE_SIMPLE);
        $variantProduct102 = $this->createProduct(102, Product::TYPE_SIMPLE);

        $prices = [
            1 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
            100 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
            101 => $this->getPricesArray(5, 1, 'USD', ['each', 'set']),
            102 => $this->getPricesArray(6, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices(
            [$simpleProduct1, $configurableProduct100, $variantProduct101, $variantProduct102],
            [
                100 => [
                    101 => $variantProduct101,
                    102 => $variantProduct102,
                ]
            ],
            $prices
        );

        $this->assertEquals(
            [
                1 => [
                    'each_1' => $this->createFormattedPrice(20, 'USD', 1, 'each')
                ],
                100 => [
                    'each_1' => $this->createFormattedPrice(10, 'USD', 1, 'each')
                ],
                101 => [
                    'each_1' => $this->createFormattedPrice(5, 'USD', 1, 'each')
                ],
            ],
            $this->provider->getByProducts([$simpleProduct1, $configurableProduct100, $variantProduct101])
        );
    }

    /**
     * @param string $unitCode
     * @param boolean $sell
     * @return ProductUnitPrecision
     */
    private function createUnitPrecision($unitCode, $sell)
    {
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setSell($sell);
        $productUnitPrecision->setUnit($this->getUnit($unitCode));

        return $productUnitPrecision;
    }

    /**
     * @param int $id
     * @param string $type
     * @return Product|object
     */
    private function createProduct($id, $type)
    {
        return $this->getEntity(
            Product::class,
            [
                'id' => $id,
                'type' => $type,
                'unitPrecisions' => [
                    $this->createUnitPrecision('each', true),
                    $this->createUnitPrecision('set', false)
                ],
            ]
        );
    }

    /**
     * @param string $unitCode
     * @return ProductUnit
     */
    private function getUnit($unitCode)
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        return $unit;
    }

    /**
     * @return array
     */
    public function isPriceBlockVisibleByProductDataProvider()
    {
        $configurableProduct1 = $this->getEntity(Product::class, [
            'id' => 1,
            'type' => Product::TYPE_CONFIGURABLE,
            'unitPrecisions' => [$this->createUnitPrecision('each', true)],
        ]);
        $variant101 = $this->getEntity(Product::class, [
            'id' => 101,
            'type' => Product::TYPE_SIMPLE,
            'unitPrecisions' => [$this->createUnitPrecision('each', true)],
        ]);
        $variant102 = $this->getEntity(Product::class, [
            'id' => 102,
            'type' => Product::TYPE_SIMPLE,
            'unitPrecisions' => [$this->createUnitPrecision('each', true)],
        ]);
        $simpleProduct2 = $this->getEntity(Product::class, [
            'id' => 2,
            'type' => Product::TYPE_SIMPLE,
            'unitPrecisions' => [$this->createUnitPrecision('each', true)],
        ]);

        return [
            'configurable product with prices' => [
                'checkedProduct' => $configurableProduct1,
                'products' => [$configurableProduct1, $variant101, $variant102],
                'variants' => [
                    1 => [
                        101 => $variant101,
                        102 => $variant102,
                    ],
                ],
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
                'variants' => [
                    1 => [
                        101 => $variant101,
                        102 => $variant102,
                    ],
                ],
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

    /**
     * @param Product[] $products
     * @param array $variants
     * @param array $prices
     */
    private function expectProductsAndPrices(array $products, array $variants, array $prices)
    {
        $currency = 'USD';

        $this->userCurrencyManager
            ->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->scopeCriteriaRequestHandler->expects($this->any())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->productPriceProvider
            ->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($scopeCriteria, $products, [$currency])
            ->willReturn($prices);

        $this->productVariantAvailabilityProvider
            ->expects($this->any())
            ->method('getSimpleProductsGroupedByConfigurable')
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
                            )] = $this->createFormattedPrice($priceValue, $priceCurrency, $qty, $unitCode);
                        }
                    }
                }

                return $formattedProductsPrices;
            });
    }

    private function expectNoExternalServiceCalled()
    {
        $this->userCurrencyManager->expects($this->never())->method('getUserCurrency');
        $this->productPriceProvider->expects($this->never())->method('getPricesByScopeCriteriaAndProducts');
        $this->productVariantAvailabilityProvider->expects($this->never())->method('getSimpleProductsByVariantFields');
        $this->productPriceFormatter->expects($this->never())->method('formatProducts');
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param string $currency
     * @param array $unitCodes
     * @return array
     */
    private function getPricesArray($price, $quantity, $currency, array $unitCodes)
    {
        return array_map(function ($unitCode) use ($price, $quantity, $currency) {
            return $this->createPrice($price, $currency, $quantity, $unitCode);
        }, $unitCodes);
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param string $currency
     * @param string $unitCode
     * @return ProductPriceDTO
     */
    private function createPrice($price, $currency, $quantity, $unitCode)
    {
        return new ProductPriceDTO(
            $this->getEntity(Product::class, ['id' => 1]),
            Price::create($price, $currency),
            $quantity,
            $this->getEntity(ProductUnit::class, ['code' => $unitCode])
        );
    }

    /**
     * @param float $priceValue
     * @param string $priceCurrency
     * @param float $qty
     * @param string $unitCode
     * @return array
     */
    private function createFormattedPrice($priceValue, $priceCurrency, $qty, $unitCode): array
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
}
