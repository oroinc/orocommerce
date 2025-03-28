<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Provider;

use Brick\Math\BigDecimal;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceDTO;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\ShippingLineItemTrait;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingCostProviderTest extends TestCase
{
    use ShippingLineItemTrait;

    private Registry|MockObject $registry;

    private PriceAttributePricesProvider|MockObject $priceProvider;

    private ObjectRepository|MockObject $repository;

    private PriceAttributePriceList $priceAttribute;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private ProductPriceScopeCriteriaInterface|MockObject $productPriceScopeCriteria;

    private ProductPriceProviderInterface|MockObject $productPriceProvider;

    private ShippingCostProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->priceProvider = $this->createMock(PriceAttributePricesProvider::class);
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->productPriceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceAttribute = new PriceAttributePriceList();

        $this->priceScopeCriteriaFactory
            ->expects(self::any())
            ->method('createByContext')
            ->willReturn($this->productPriceScopeCriteria);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($this->priceAttribute);
    }

    public function testCannotFoundPriceListShippingCostAttribute(): void
    {
        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $provider = new ShippingCostProvider(
            $this->priceProvider,
            $this->registry,
            $this->priceScopeCriteriaFactory,
            $this->productPriceProvider
        );

        [$subtotal, $shipping] = $provider->getCalculatedProductShippingCost(
            new Checkout(),
            new ArrayCollection([]),
            'USD'
        );

        self::assertEquals(0.0, $subtotal->toFloat());
        self::assertEquals(0.0, $shipping->toFloat());
    }

    public function testCannotFoundProduct(): void
    {
        $this->priceProvider->expects(self::never())
            ->method('getPricesWithUnitAndCurrencies');

        $provider = new ShippingCostProvider(
            $this->priceProvider,
            $this->registry,
            $this->priceScopeCriteriaFactory,
            $this->productPriceProvider
        );

        $lineItem = $this->getShippingLineItem(unitCode: 'piece');

        $lineItems = new ArrayCollection([$lineItem]);

        [$subtotal, $shipping] = $provider->getCalculatedProductShippingCost(
            new Checkout(),
            $lineItems,
            'USD'
        );

        self::assertEquals(0.0, $subtotal->toFloat());
        self::assertEquals(0.0, $shipping->toFloat());
    }

    public function testCannotFoundProductUnitCode(): void
    {
        $this->priceProvider->expects(self::once())
            ->method('getPricesWithUnitAndCurrencies')
            ->with($this->priceAttribute, new Product())
            ->willReturn([]);

        $provider = new ShippingCostProvider(
            $this->priceProvider,
            $this->registry,
            $this->priceScopeCriteriaFactory,
            $this->productPriceProvider
        );

        $lineItem = $this->getShippingLineItem(unitCode: 'piece')
            ->setProduct(new Product());
        $lineItems = new ArrayCollection([$lineItem]);

        [$subtotal, $shipping] = $provider->getCalculatedProductShippingCost(
            new Checkout(),
            $lineItems,
            'USD'
        );

        self::assertEquals(0.0, $subtotal->toFloat());
        self::assertEquals(0.0, $shipping->toFloat());
    }

    public function testCannotFoundCurrency(): void
    {
        $this->priceProvider->expects(self::once())
            ->method('getPricesWithUnitAndCurrencies')
            ->with($this->priceAttribute, new Product())
            ->willReturn(['piece' => []]);

        $provider = new ShippingCostProvider(
            $this->priceProvider,
            $this->registry,
            $this->priceScopeCriteriaFactory,
            $this->productPriceProvider
        );

        $lineItem = $this->getShippingLineItem(unitCode: 'piece')
            ->setProduct(new Product());
        $lineItems = new ArrayCollection([$lineItem]);

        [$subtotal, $shipping] = $provider->getCalculatedProductShippingCost(
            new Checkout(),
            $lineItems,
            'USD'
        );

        self::assertEquals(0.0, $subtotal->toFloat());
        self::assertEquals(0.0, $shipping->toFloat());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @dataProvider simpleProductsShippingCostDataProvider
     */
    public function testGetCalculatedSimpleProductsShippingCost(
        array $lineItemsArray,
        string $unitCode,
        string $currency,
        ?array $expectedResult
    ): void {
        $checkout = new Checkout();
        $lineItems = new ArrayCollection([]);
        $productPrices = [];

        $provider = new ShippingCostProvider(
            $this->priceProvider,
            $this->registry,
            $this->priceScopeCriteriaFactory,
            $this->productPriceProvider
        );

        foreach ($lineItemsArray as $itemKey => $item) {
            $shippingLineItem = $this->getShippingLineItem(
                quantity: $item['quantity'],
                unitCode: $item['unitCode']
            )->setPrice($item['price']);
            $product = $this->createEmptyProduct(sku: $itemKey, unitCode: $unitCode);
            $productPrices[$itemKey] = $item['shippingPrice']->setProduct($product);
            $shippingLineItem->setProduct($product);
            $lineItems->add($shippingLineItem);
        }

        $this->priceAttribute->setCurrencies([$currency, 'NotExistingCurrency']);
        $this->priceProvider->expects(self::any())
            ->method('getPricesWithUnitAndCurrencies')
            ->willReturnCallback(function ($priceAttribute, $product) use ($productPrices) {
                $data = [];
                foreach ($product->getAvailableUnitCodes() as $unitCode) {
                    $prices = [];
                    foreach ($priceAttribute->getCurrencies() as $currency) {
                        $prices[$currency] = $productPrices[$product->getSku()];
                    }

                    $data[$unitCode] = $prices;
                }

                return $data;
            });

        $result = $provider->getCalculatedProductShippingCost(
            $checkout,
            $lineItems,
            $currency
        );

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function simpleProductsShippingCostDataProvider(): array
    {
        return [
            'Simple products with prices' => [
                'lineItemsArray' => [
                    'simple1' => [
                        'quantity' => 3,
                        'unitCode' => 'piece',
                        'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                            Price::create(11.22, 'USD')
                        ),
                        'price' => Price::create(22.11, 'USD')
                    ],
                    'simple2' => [
                        'quantity' => 2,
                        'unitCode' => 'set',
                        'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                            Price::create(11.22, 'USD')
                        ),
                        'price' => Price::create(22.11, 'USD')
                    ],
                    'simple3' => [
                        'quantity' => 4,
                        'unitCode' => 'piece',
                        'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                            Price::create(11.22, 'USD')
                        ),
                        'price' => Price::create(22.11, 'USD')
                    ],
                ],
                'unitCode' => 'piece',
                'currency' => 'USD',
                'expectedResult' => [BigDecimal::of(198.99), BigDecimal::of(78.54)]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @dataProvider productKitShippingCostDataProvider
     */
    public function testGetCalculatedProductKitShippingCost(
        array $lineItemsArray,
        string $unitCode,
        string $currency,
        ?array $expectedResult
    ): void {
        $provider = new ShippingCostProvider(
            $this->priceProvider,
            $this->registry,
            $this->priceScopeCriteriaFactory,
            $this->productPriceProvider
        );

        $checkout = new Checkout();
        $lineItems = new ArrayCollection([]);
        $productPrices = [];

        foreach ($lineItemsArray as $itemKey => $item) {
            $shippingLineItem = $this->getShippingLineItem(
                quantity: $item['quantity'],
                unitCode: $item['unitCode']
            )->setPrice($item['price']);
            $product = $this
                ->createEmptyProduct($itemKey, Product::TYPE_KIT, $unitCode)
                ->setKitShippingCalculationMethod($item['kitShippingCalculationMethod']);
            ReflectionUtil::setId($product, 1);
            $this->productPriceProvider
                ->expects(self::any())
                ->method('getPricesByScopeCriteriaAndProducts')
                ->with($this->productPriceScopeCriteria, [$product], [$currency])
                ->willReturn([
                    1 => [
                        new ProductKitPriceDTO(
                            $product,
                            $item['price'],
                            $item['quantity'],
                            (new ProductUnit())->setCode($item['unitCode'])->setDefaultPrecision(0)
                        )
                    ]
                ]);

            $productPrices[$itemKey] = [
                'shippingPrice' => $item['shippingPrice']->setProduct($product),
                'price' => $item['price']
            ];

            if ($product->isKit()) {
                $kitItemLineItems = new ArrayCollection([]);
                foreach ($item['kitLineItemsArray'] as $lineItemKey => $lineItem) {
                    $itemProduct = $this->createEmptyProduct(sku: $lineItemKey, unitCode: $lineItem['unitCode']);
                    $productPrices[$lineItemKey] = [
                        'shippingPrice' => $lineItem['shippingPrice']->setProduct($itemProduct),
                        'price' => $lineItem['price']
                    ];
                    $kitItemLineItems->add(
                        $this->getShippingKitItemLineItem(
                            $itemProduct,
                            $lineItem['price'],
                            $lineItem['quantity'],
                            $lineItem['unitCode']
                        )->setProduct($itemProduct)
                    );
                }
                $shippingLineItem->setKitItemLineItems($kitItemLineItems);
            }
            $shippingLineItem->setProduct($product);
            $lineItems->add($shippingLineItem);
        }

        $this->priceAttribute->setCurrencies([$currency, 'NotExistingCurrency']);
        $this->priceProvider->expects(self::any())
            ->method('getPricesWithUnitAndCurrencies')
            ->willReturnCallback(function ($priceAttribute, $product) use ($productPrices) {
                $data = [];
                foreach ($product->getAvailableUnitCodes() as $unitCode) {
                    $prices = [];
                    foreach ($priceAttribute->getCurrencies() as $currency) {
                        $prices[$currency] = $productPrices[$product->getSku()]['shippingPrice'];
                    }

                    $data[$unitCode] = $prices;
                }

                return $data;
            });

        $result = $provider->getCalculatedProductShippingCost(
            $checkout,
            $lineItems,
            $currency
        );

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function productKitShippingCostDataProvider(): array
    {
        return [
            'Product kit with price containing line items with prices, kit shipping calculate as all' => [
                'lineItemsArray' => [
                    'kit1' => [
                        'quantity' => 1,
                        'unitCode' => 'each',
                        'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ALL,
                        'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                            Price::create(1.50, 'USD')
                        ),
                        'price' => Price::create(10.50, 'USD'),
                        'kitLineItemsArray' => [
                            'sku1' => [
                                'quantity' => 3,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(2.25, 'USD')
                                ),
                                'price' => Price::create(12.25, 'USD'),
                            ],
                            'sku2' => [
                                'quantity' => 2,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(3.50, 'USD')
                                ),
                                'price' => Price::create(35.50, 'USD'),
                            ],
                            'sku3' => [
                                'quantity' => 4,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(5.25, 'USD')
                                ),
                                'price' => Price::create(56.25, 'USD'),
                            ],
                        ]
                    ],
                ],
                'unitCode' => 'each',
                'currency' => 'USD',
                'expectedResult' => [BigDecimal::of(343.25), BigDecimal::of(36.25)]
            ],
            'Product kit with price containing line items with prices, kit shipping calculate as items' => [
                'lineItemsArray' => [
                    'kit1' => [
                        'quantity' => 1,
                        'unitCode' => 'each',
                        'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ONLY_ITEMS,
                        'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                            Price::create(1.50, 'USD')
                        ),
                        'price' => Price::create(10.50, 'USD'),
                        'kitLineItemsArray' => [
                            'sku1' => [
                                'quantity' => 3,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(2.25, 'USD')
                                ),
                                'price' => Price::create(12.25, 'USD'),
                            ],
                            'sku2' => [
                                'quantity' => 2,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(3.50, 'USD')
                                ),
                                'price' => Price::create(35.50, 'USD'),
                            ],
                            'sku3' => [
                                'quantity' => 4,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(5.25, 'USD')
                                ),
                                'price' => Price::create(56.25, 'USD'),
                            ],
                        ]
                    ],
                ],
                'unitCode' => 'each',
                'currency' => 'USD',
                'expectedResult' => [BigDecimal::of(332.75), BigDecimal::of(34.75)]
            ],
            'Product kit with price containing line items with, kit shipping calculate as product' => [
                'lineItemsArray' => [
                    'kit1' => [
                        'quantity' => 1,
                        'unitCode' => 'each',
                        'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ONLY_PRODUCT,
                        'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                            Price::create(1.50, 'USD')
                        ),
                        'price' => Price::create(10.50, 'USD'),
                        'kitLineItemsArray' => [
                            'sku1' => [
                                'quantity' => 3,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(2.25, 'USD')
                                ),
                                'price' => Price::create(12.25, 'USD'),
                            ],
                            'sku2' => [
                                'quantity' => 2,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(3.50, 'USD')
                                ),
                                'price' => Price::create(35.50, 'USD'),
                            ],
                            'sku3' => [
                                'quantity' => 4,
                                'unitCode' => 'each',
                                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                                    Price::create(5.25, 'USD')
                                ),
                                'price' => Price::create(56.25, 'USD'),
                            ],
                        ]
                    ],
                ],
                'unitCode' => 'each',
                'currency' => 'USD',
                'expectedResult' => [BigDecimal::of(10.5), BigDecimal::of(1.5)]
            ],
        ];
    }


    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)*
     */
    public function testGetCalculatedProductKitShippingCostWithoutPrices(): void
    {
        $unitCode = 'each';
        $currency = 'USD';
        $lineItemsArray = [
            'kit1' => [
                'quantity' => 1,
                'unitCode' => 'each',
                'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ALL,
                'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                    Price::create(1.50, 'USD')
                ),
                'price' => null,
                'kitLineItemsArray' => [
                    'sku1' => [
                        'quantity' => 3,
                        'unitCode' => 'each',
                        'shippingPrice' => (new PriceAttributeProductPrice())->setPrice(
                            Price::create(2.25, 'USD')
                        ),
                        'price' => Price::create(12.25, 'USD'),
                    ],
                ]
            ],
        ];

        $provider = new ShippingCostProvider(
            $this->priceProvider,
            $this->registry,
            $this->priceScopeCriteriaFactory,
            $this->productPriceProvider
        );

        $checkout = new Checkout();
        $lineItems = new ArrayCollection([]);
        $productPrices = [];

        foreach ($lineItemsArray as $itemKey => $item) {
            $shippingLineItem = $this->getShippingLineItem(
                quantity: $item['quantity'],
                unitCode: $item['unitCode']
            )->setPrice($item['price']);
            $product = $this
                ->createEmptyProduct($itemKey, Product::TYPE_KIT, $unitCode)
                ->setKitShippingCalculationMethod($item['kitShippingCalculationMethod']);

            ReflectionUtil::setId($product, 1);

            $this->productPriceProvider
                ->expects(self::any())
                ->method('getPricesByScopeCriteriaAndProducts')
                ->with($this->productPriceScopeCriteria, [$product], [$currency])
                ->willReturn([]);

            $productPrices[$itemKey] = [
                'shippingPrice' => $item['shippingPrice']->setProduct($product),
                'price' => $item['price']
            ];

            if ($product->isKit()) {
                $kitItemLineItems = new ArrayCollection([]);
                foreach ($item['kitLineItemsArray'] as $lineItemKey => $lineItem) {
                    $itemProduct = $this->createEmptyProduct(sku: $lineItemKey, unitCode: $lineItem['unitCode']);
                    $productPrices[$lineItemKey] = [
                        'shippingPrice' => $lineItem['shippingPrice']->setProduct($itemProduct),
                        'price' => $lineItem['price']
                    ];
                    $kitItemLineItems->add(
                        $this->getShippingKitItemLineItem(
                            $itemProduct,
                            $lineItem['price'],
                            $lineItem['quantity'],
                            $lineItem['unitCode']
                        )->setProduct($itemProduct)
                    );
                }
                $shippingLineItem->setKitItemLineItems($kitItemLineItems);
            }
            $shippingLineItem->setProduct($product);
            $lineItems->add($shippingLineItem);
        }

        $this->priceAttribute->setCurrencies([$currency, 'NotExistingCurrency']);
        $this->priceProvider->expects(self::any())
            ->method('getPricesWithUnitAndCurrencies')
            ->willReturnCallback(function ($priceAttribute, $product) use ($productPrices) {
                $data = [];
                foreach ($product->getAvailableUnitCodes() as $unitCode) {
                    $prices = [];
                    foreach ($priceAttribute->getCurrencies() as $currency) {
                        $prices[$currency] = $productPrices[$product->getSku()]['shippingPrice'];
                    }

                    $data[$unitCode] = $prices;
                }

                return $data;
            });

        $result = $provider->getCalculatedProductShippingCost(
            $checkout,
            $lineItems,
            $currency
        );

        self::assertEquals([BigDecimal::of(36.75), BigDecimal::of(8.25)], $result);
    }

    private function createEmptyProduct(
        string $sku,
        string $type = Product::TYPE_SIMPLE,
        string $unitCode = 'each',
        int $precision = 0,
        int $defaultPrecision = 0
    ): Product {
        $product = new Product();
        $product
            ->setSku($sku)
            ->setType($type)
            ->addUnitPrecision(
                (new ProductUnitPrecision())
                    ->setUnit(
                        (new ProductUnit())
                            ->setCode($unitCode)
                            ->setDefaultPrecision($defaultPrecision)
                    )
                    ->setProduct($product)
                    ->setPrecision($precision)
            );

        return $product;
    }
}
