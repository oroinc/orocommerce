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
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductUnitsQuantityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FrontendProductPricesProviderTest extends TestCase
{
    private UserCurrencyManager|MockObject $userCurrencyManager;

    private ProductPriceFormatter|MockObject $productPriceFormatter;

    private ProductVariantAvailabilityProvider|MockObject $productVariantAvailabilityProvider;

    private ProductPriceScopeCriteriaRequestHandler|MockObject $scopeCriteriaRequestHandler;

    private ProductPriceProviderInterface|MockObject $productPriceProvider;

    private FrontendProductUnitsProvider|MockObject $productUnitsProvider;

    private FrontendShoppingListProductUnitsQuantityProvider|MockObject $shoppingListProvider;

    private FrontendProductPricesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->productPriceFormatter = $this->createMock(ProductPriceFormatter::class);
        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->productUnitsProvider = $this->createMock(FrontendProductUnitsProvider::class);
        $this->shoppingListProvider = $this->createMock(FrontendShoppingListProductUnitsQuantityProvider::class);

        $this->provider = new FrontendProductPricesProvider(
            $this->scopeCriteriaRequestHandler,
            $this->productVariantAvailabilityProvider,
            $this->userCurrencyManager,
            $this->productPriceFormatter,
            $this->productPriceProvider,
            $this->productUnitsProvider,
            $this->shoppingListProvider
        );
    }

    /**
     * @dataProvider getByProductSimpleDataProvider
     */
    public function testGetByProductSimple(Product|ProductView $simpleProduct): void
    {
        $prices = [
            42 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices([$simpleProduct], [], $prices);

        self::assertEquals(
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
    public function testGetByProductConfigurable(Product|ProductView $configurableProduct): void
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

        self::assertEquals(
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

    public function testGetVariantsPricesByProductSimple(): void
    {
        $simpleProduct1 = $this->getProduct(42, Product::TYPE_SIMPLE);

        $prices = [
            42 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices([$simpleProduct1], [], $prices);

        self::assertEquals([], $this->provider->getVariantsPricesByProduct($simpleProduct1));
    }

    public function testGetVariantsPricesByProductConfigurable(): void
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

        self::assertEquals(
            [
                101 => [$this->getFormattedPrice(5, 'USD', 1, 'each')],
                102 => [$this->getFormattedPrice(6, 'USD', 1, 'each')]
            ],
            $this->provider->getVariantsPricesByProduct($configurableProduct100)
        );
    }

    public function testGetByProductsEmptyProducts(): void
    {
        self::assertSame([], $this->provider->getByProducts([]));
    }

    public function testGetByProducts(): void
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

        self::assertEquals(
            [
                1 => [$this->getFormattedPrice(20, 'USD', 1, 'each')],
                100 => [$this->getFormattedPrice(10, 'USD', 1, 'each')],
                101 => [$this->getFormattedPrice(5, 'USD', 1, 'each')]
            ],
            $this->provider->getByProducts([$simpleProduct1, $configurableProduct100, $variantProduct101])
        );
    }

    /**
     * @dataProvider getByProductSimpleDataProvider
     */
    public function testGetShoppingListPricesByProductSimple(Product|ProductView $simpleProduct): void
    {
        $prices = [
            42 => $this->getPricesArray(10, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices([$simpleProduct], [], $prices);

        $this->shoppingListProvider
            ->expects(self::once())
            ->method('getByProduct')
            ->with($simpleProduct)
            ->willReturn($this->generateShoppingListResultForProducts([$simpleProduct])[$simpleProduct->getId()]);

        self::assertEquals(
            ['each' => $this->getFormattedPrice(10, 'USD', 1, 'each'), 'set' => null],
            $this->provider->getShoppingListPricesByProduct($simpleProduct)
        );
    }

    /**
     * @dataProvider getByProductConfigurableDataProvider
     */
    public function testGetShoppingListPricesByProductConfigurable(Product|ProductView $configurableProduct): void
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

        $this->shoppingListProvider
            ->expects(self::any())
            ->method('getByProduct')
            ->with($configurableProduct)
            ->willReturn(
                $this->generateShoppingListResultForProducts([$configurableProduct])[$configurableProduct->getId()]
            );

        self::assertEquals(
            ['each' => $this->getFormattedPrice(10, 'USD', 1, 'each'), 'set' => null],
            $this->provider->getShoppingListPricesByProduct($configurableProduct)
        );
    }

    public function testGetShoppingListPricesByProduct(): void
    {
        $simpleProduct1 = $this->getProductView(1, Product::TYPE_SIMPLE);

        $prices = [
            1 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
        ];

        $this->expectProductsAndPrices(
            [$simpleProduct1],
            [],
            $prices
        );

        $this->shoppingListProvider
            ->expects(self::once())
            ->method('getByProduct')
            ->with($simpleProduct1)
            ->willReturn($this->generateShoppingListResultForProducts([$simpleProduct1])[$simpleProduct1->getId()]);

        self::assertEquals(
            ['each' => $this->getFormattedPrice(20, 'USD', 1, 'each'), 'set' => null],
            $this->provider->getShoppingListPricesByProduct($simpleProduct1)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function shoppingListPricesDataProvider(): array
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
        $configurableProduct2 = $this->getProduct(
            2,
            Product::TYPE_CONFIGURABLE,
            [
                $this->getUnitPrecision('each', true),
                $this->getUnitPrecision('set', true)
            ]
        );
        $variant201 = $this->getProduct(
            201,
            Product::TYPE_SIMPLE,
            [
                $this->getUnitPrecision('each', true),
                $this->getUnitPrecision('set', true)
            ]
        );
        $variant202 = $this->getProduct(
            202,
            Product::TYPE_SIMPLE,
            [
                $this->getUnitPrecision('each', true),
                $this->getUnitPrecision('set', true)
            ]
        );
        $simpleProduct3 = $this->getProduct(
            3,
            Product::TYPE_SIMPLE,
            [$this->getUnitPrecision('each', true)]
        );

        $simpleProduct4 = $this->getProduct(
            4,
            Product::TYPE_SIMPLE,
            [
                $this->getUnitPrecision('each', true),
                $this->getUnitPrecision('set', true)
            ]
        );

        return [
            'configurable product' => [
                'shoppingListResultForProducts' => [$configurableProduct1, $variant101, $variant102],
                'products' => [$configurableProduct1, $variant101, $variant102],
                'variants' => [1 => [101, 102]],
                'prices' => [
                    1 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
                    101 => $this->getPricesArray(30, 1, 'USD', ['each', 'set']),
                    102 => $this->getPricesArray(40, 1, 'USD', ['each', 'set']),
                ],
                'expected' => [
                    1 => [
                        'each' => $this->getFormattedPrice(20, 'USD', 1, 'each'),
                        'set' => null
                    ],
                    101 => [
                        'each' => $this->getFormattedPrice(30, 'USD', 1, 'each'),
                        'set' => null
                    ],
                    102 => [
                        'each' => $this->getFormattedPrice(40, 'USD', 1, 'each'),
                        'set' => null
                    ],
                ]
            ],
            'configurable product without prices' => [
                'shoppingListResultForProducts' => [$configurableProduct1, $variant101, $variant102],
                'products' => [$configurableProduct1, $variant101, $variant102],
                'variants' => [1 => [101, 102]],
                'prices' => [],
                'expected' => [
                    1 => ['each' => null, 'set' => null],
                    101 => ['each' => null, 'set' => null],
                    102 => ['each' => null, 'set' => null],
                ]
            ],
            'configurable product with few unit precisions' => [
                'shoppingListResultForProducts' => [$configurableProduct2, $variant201, $variant202],
                'products' => [$configurableProduct2, $variant201, $variant202],
                'variants' => [2 => [201, 202]],
                'prices' => [
                    2 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
                    201 => $this->getPricesArray(30, 1, 'USD', ['each', 'set']),
                    202 => $this->getPricesArray(40, 1, 'USD', ['each', 'set']),
                ],
                'expected' => [
                    2 => [
                        'each' => $this->getFormattedPrice(20, 'USD', 1, 'each'),
                        'set' => $this->getFormattedPrice(20, 'USD', 1, 'set', true)
                    ],
                    201 => [
                        'each' => $this->getFormattedPrice(30, 'USD', 1, 'each'),
                        'set' => $this->getFormattedPrice(30, 'USD', 1, 'set', true)
                    ],
                    202 => [
                        'each' => $this->getFormattedPrice(40, 'USD', 1, 'each'),
                        'set' => $this->getFormattedPrice(40, 'USD', 1, 'set', true)
                    ],
                ]
            ],
            'configurable product without price for one of unit precision' => [
                'shoppingListResultForProducts' => [$configurableProduct2, $variant201, $variant202],
                'products' => [$configurableProduct2, $variant201, $variant202],
                'variants' => [2 => [201, 202]],
                'prices' => [
                    2 => $this->getPricesArray(20, 1, 'USD', ['each']),
                    201 => $this->getPricesArray(30, 1, 'USD', ['each']),
                    202 => $this->getPricesArray(40, 1, 'USD', ['each']),
                ],
                'expected' => [
                    2 => [
                        'each' => $this->getFormattedPrice(20, 'USD', 1, 'each'),
                        'set' => null
                    ],
                    201 => [
                        'each' => $this->getFormattedPrice(30, 'USD', 1, 'each'),
                        'set' => null
                    ],
                    202 => [
                        'each' => $this->getFormattedPrice(40, 'USD', 1, 'each'),
                        'set' => null
                    ],
                ]
            ],
            'configurable product without shopping lists' => [
                'shoppingListResultForProducts' => [],
                'products' => [$configurableProduct2, $variant201, $variant202],
                'variants' => [2 => [201, 202]],
                'prices' => [
                    2 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
                    201 => $this->getPricesArray(30, 1, 'USD', ['each', 'set']),
                    202 => $this->getPricesArray(40, 1, 'USD', ['each', 'set']),
                ],
                'expected' => []
            ],
            'simple product with prices' => [
                'shoppingListResultForProducts' => [$simpleProduct3],
                'products' => [$simpleProduct3],
                'variants' => [],
                'prices' => [
                    3 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
                ],
                'expected' => [
                    3 => [
                        'each' => $this->getFormattedPrice(20, 'USD', 1, 'each'),
                        'set' => null
                    ]
                ]
            ],
            'simple product without shopping lists' => [
                'shoppingListResultForProducts' => [],
                'products' => [$simpleProduct3],
                'variants' => [],
                'prices' => [
                    3 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
                ],
                'expected' => []
            ],
            'simple product without prices' => [
                'shoppingListResultForProducts' => [$simpleProduct3],
                'products' => [$simpleProduct3],
                'variants' => [],
                'prices' => [],
                'expected' => [
                    3 => ['each' => null, 'set' => null]
                ]
            ],
            'simple product with few unit precisions' => [
                'shoppingListResultForProducts' => [$simpleProduct4],
                'products' => [$simpleProduct4],
                'variants' => [],
                'prices' => [
                    4 => $this->getPricesArray(20, 1, 'USD', ['each', 'set']),
                ],
                'expected' => [
                    4 => [
                        'each' => $this->getFormattedPrice(20, 'USD', 1, 'each'),
                        'set' => $this->getFormattedPrice(20, 'USD', 1, 'set', true)
                    ]
                ]
            ],
            'simple product without price for one of unit precision' => [
                'shoppingListResultForProducts' => [$simpleProduct4],
                'products' => [$simpleProduct4],
                'variants' => [],
                'prices' => [
                    4 => $this->getPricesArray(20, 1, 'USD', ['set']),
                ],
                'expected' => [
                    4 => [
                        'each' => null,
                        'set' => $this->getFormattedPrice(20, 'USD', 1, 'set')
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider shoppingListPricesDataProvider
     */
    public function testGetShoppingListPricesByProducts(
        array $shoppingListResultForProducts,
        array $products,
        array $variants,
        array $prices,
        array $expected
    ): void {
        $this->expectProductsAndPrices(
            $products,
            $variants,
            $prices
        );

        $this->shoppingListProvider
            ->expects(self::any())
            ->method('getByProducts')
            ->with($products)
            ->willReturn($this->generateShoppingListResultForProducts($shoppingListResultForProducts));

        self::assertEquals(
            $expected,
            $this->provider->getShoppingListPricesByProducts($products)
        );
    }

    private function generateShoppingListResultForProducts(array $products = []): array
    {
        $shoppingList = [];
        foreach ($products as $product) {
            $shoppingList[$product->getId()] = [
                [
                    'id' => 1,
                    'label' => 'Shopping List',
                    'is_current' => false,
                    'line_items' => [
                        [
                            'id' => 1,
                            'productId' => $product->getId(),
                            'unit' => 'each',
                            'quantity' => 100.0,
                        ],
                        [
                            'id' => 2,
                            'productId' => $product->getId(),
                            'unit' => 'set',
                            'quantity' => 500.0,
                        ]
                    ]
                ],
                [
                    'id' => 2,
                    'label' => 'Shopping List 2',
                    'is_current' => true,
                    'line_items' => [
                        [
                            'id' => 1,
                            'productId' => $product->getId(),
                            'unit' => 'each',
                            'quantity' => 10.0,
                        ],
                        [
                            'id' => 2,
                            'productId' => $product->getId(),
                            'unit' => 'set',
                            'quantity' => 50.0,
                        ]
                    ]
                ],
            ];
        }

        return $shoppingList;
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
    ): void {
        if ($expectedNoExternalServiceCalled) {
            $this->expectNoExternalServiceCalled();
        } else {
            $this->expectProductsAndPrices($products, $variants, $prices);
        }

        self::assertEquals($expected, $this->provider->isShowProductPriceContainer($checkedProduct));
    }

    private function expectProductsAndPrices(array $products, array $variants, array $prices): void
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

        $this->userCurrencyManager->expects(self::any())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->scopeCriteriaRequestHandler->expects(self::any())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->productPriceProvider->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($scopeCriteria, $productIds, [$currency])
            ->willReturn($prices);

        $this->productUnitsProvider->expects(self::once())
            ->method('getUnitsForProducts')
            ->with($productIds)
            ->willReturn($productUnits);

        $this->productVariantAvailabilityProvider->expects(self::any())
            ->method('getSimpleProductIdsGroupedByConfigurable')
            ->willReturn($variants);

        $this->productPriceFormatter->expects(self::any())
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

    private function expectNoExternalServiceCalled(): void
    {
        $this->userCurrencyManager->expects(self::never())
            ->method('getUserCurrency');
        $this->productPriceProvider->expects(self::never())
            ->method('getPricesByScopeCriteriaAndProducts');
        $this->productUnitsProvider->expects(self::never())
            ->method('getUnitsForProducts');
        $this->productVariantAvailabilityProvider->expects(self::never())
            ->method('getSimpleProductIdsGroupedByConfigurable');
        $this->productPriceFormatter->expects(self::never())
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

    private function getFormattedPrice(
        float $priceValue,
        string $priceCurrency,
        float $qty,
        string $unitCode,
        bool $hasDiscount = false
    ): array {
        return [
            'price' => $priceValue,
            'currency' => $priceCurrency,
            'quantity' => $qty,
            'unit' => $unitCode,
            'formatted_price' => $priceValue . ' ' . $priceCurrency,
            'formatted_unit' => $unitCode . ' FORMATTED',
            'quantity_with_unit' => $qty . ' ' . $unitCode,
            'hasDiscount' => $hasDiscount,
        ];
    }

    private function getProductView(int $id, string $type, ?array $units = null): ProductView
    {
        $product = new ProductView();
        $product->set('id', $id);
        $product->set('type', $type);
        $product->set('product_units', null === $units ? ['each' => 0] : array_fill_keys($units, 0));

        return $product;
    }

    private function getProduct(int $id, string $type, ?array $unitPrecisions = null): ProductStub
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
