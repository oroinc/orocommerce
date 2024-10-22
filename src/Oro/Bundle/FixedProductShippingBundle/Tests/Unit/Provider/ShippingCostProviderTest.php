<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Provider;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
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
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingCostProviderTest extends TestCase
{
    private const LINE_ITEM_UNIT_CODE = 'item';
    private const LINE_ITEM_QUANTITY = 15;
    private const LINE_ITEM_ENTITY_ID = 1;
    private const KIT_ITEM_LINE_ITEM_ENTITY_ID = 2;

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

        $this->initShippingCostProvider();

        [$subtotal, $shipping] = $this->provider->getCalculatedProductShippingCostWithSubtotal(
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

        $this->initShippingCostProvider();

        $lineItem = $this->getShippingLineItem(unitCode: 'piece');

        $lineItems = new ArrayCollection([$lineItem]);

        [$subtotal, $shipping] = $this->provider->getCalculatedProductShippingCostWithSubtotal(
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

        $this->initShippingCostProvider();

        $lineItem = $this->getShippingLineItem(unitCode: 'piece')
            ->setProduct(new Product());
        $lineItems = new ArrayCollection([$lineItem]);

        [$subtotal, $shipping] = $this->provider->getCalculatedProductShippingCostWithSubtotal(
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

        $this->initShippingCostProvider();

        $lineItem = $this->getShippingLineItem(unitCode: 'piece')
            ->setProduct(new Product());
        $lineItems = new ArrayCollection([$lineItem]);

        [$subtotal, $shipping] = $this->provider->getCalculatedProductShippingCostWithSubtotal(
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
     * @throws MathException
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

        $this->initShippingCostProvider();

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

        $result = $this->provider->getCalculatedProductShippingCostWithSubtotal(
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
     * @throws MathException
     */
    public function testGetCalculatedProductKitShippingCost(
        array $lineItemsArray,
        string $unitCode,
        string $currency,
        ?array $expectedResult
    ): void {
        $this->initShippingCostProvider();

        $checkout = new Checkout();
        $lineItems = new DoctrineShippingLineItemCollection([]);
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

        $result = $this->provider->getCalculatedProductShippingCostWithSubtotal(
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

    private function initShippingCostProvider(): void
    {
        $this->provider = new ShippingCostProvider(
            $this->priceProvider,
            $this->registry
        );
        $this->provider->setPriceScopeCriteriaFactory($this->priceScopeCriteriaFactory);
        $this->provider->setProductPriceProvider($this->productPriceProvider);
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

    private function getShippingLineItem(
        ?ProductUnit $productUnit = null,
        ?float $quantity = null,
        ?string $unitCode = null
    ): ShippingLineItem {
        if ($productUnit === null) {
            $productUnit = $this->createMock(ProductUnit::class);
            $productUnit->method('getCode')->willReturn($unitCode ?? static::LINE_ITEM_UNIT_CODE);
        }

        $productHolder = $this->createMock(ProductHolderInterface::class);
        $productHolder->method('getEntityIdentifier')->willReturn(static::LINE_ITEM_ENTITY_ID);

        return new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnit,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnit?->getCode(),
            ShippingLineItem::FIELD_QUANTITY => $quantity ?? static::LINE_ITEM_QUANTITY,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $productHolder,
        ]);
    }

    private function getShippingKitItemLineItem(
        ?Product $product,
        ?Price $price,
        ?float $quantity = null,
        ?string $unitCode = null,
        int $precision = 0,
        int $defaultPrecision = 0
    ): ShippingKitItemLineItem {
        $unit = (new ProductUnit())->setCode($unitCode)->setDefaultPrecision($defaultPrecision);
        $unitPrecision = (new ProductUnitPrecision())->setPrecision($precision)->setUnit($unit)->setProduct($product);
        $product->addUnitPrecision($unitPrecision);

        $productKitItemProduct = (new ProductKitItemProduct())->setProduct($product);
        $kitItem = (new ProductKitItem())->setProductUnit($unit)->addKitItemProduct($productKitItemProduct);
        $productKitItemProduct->setKitItem($kitItem);

        $productHolder = $this->createMock(ProductHolderInterface::class);
        $productHolder->method('getEntityIdentifier')->willReturn(static::KIT_ITEM_LINE_ITEM_ENTITY_ID);

        return (new ShippingKitItemLineItem($productHolder))
            ->setQuantity($quantity)
            ->setProductUnit($unit)
            ->setProductUnitCode($unitCode)
            ->setKitItem($kitItem)
            ->setPrice($price);
    }
}
