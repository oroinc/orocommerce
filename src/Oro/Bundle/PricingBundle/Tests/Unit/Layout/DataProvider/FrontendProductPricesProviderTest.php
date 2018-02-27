<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductPricesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShardManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shardManager;

    /**
     * @var FrontendProductPricesProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userCurrencyManager;

    /**
     * @var ProductPriceFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productPriceFormatter;

    /**
     * @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productVariantAvailabilityProvider;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListRequestHandler = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Model\PriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userCurrencyManager = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productPriceFormatter = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productVariantAvailabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);
        $this->shardManager = $this->createMock(ShardManager::class);

        $this->provider = new FrontendProductPricesProvider(
            $this->doctrineHelper,
            $this->priceListRequestHandler,
            $this->productVariantAvailabilityProvider,
            $this->userCurrencyManager,
            $this->productPriceFormatter,
            $this->shardManager
        );
    }

    public function testGetByProductSimple()
    {
        $simpleProduct1 = $this->createProduct(42, Product::TYPE_SIMPLE);

        $products = [
            42 => [
                'product' => $simpleProduct1,
                'variants' => [],
            ],
        ];

        $prices = [
            $this->createProductPrice('each', $simpleProduct1),
            $this->createProductPrice('set', $simpleProduct1),
        ];

        $this->expectProductsAndPrices($products, $prices);

        $this->assertEquals(
            [
                'each' => ['price' => null, 'currency' => null, 'unit' => 'each', 'quantity' => null],
            ],
            $this->provider->getByProduct($simpleProduct1)
        );
    }

    public function testGetVariantsPricesByProductSimple()
    {
        $simpleProduct1 = $this->createProduct(42, Product::TYPE_SIMPLE);

        $products = [
            42 => [
                'product' => $simpleProduct1,
                'variants' => [],
            ],
        ];

        $prices = [
            $this->createProductPrice('each', $simpleProduct1),
            $this->createProductPrice('set', $simpleProduct1),
        ];

        $this->expectProductsAndPrices($products, $prices);

        $this->assertEquals([], $this->provider->getVariantsPricesByProduct($simpleProduct1));
    }

    public function testGetByProductConfigurable()
    {
        $configurableProduct100 = $this->createProduct(100, Product::TYPE_CONFIGURABLE);
        $variantProduct101 = $this->createProduct(101, Product::TYPE_SIMPLE);
        $variantProduct102 = $this->createProduct(102, Product::TYPE_SIMPLE);

        $products = [
            100 => [
                'product' => $configurableProduct100,
                'variants' => [
                    101 => $variantProduct101,
                    102 => $variantProduct102,
                ],
            ],
        ];

        $prices = [
            $this->createProductPrice('each', $configurableProduct100),
            $this->createProductPrice('set', $configurableProduct100),
            $this->createProductPrice('each', $variantProduct101),
            $this->createProductPrice('set', $variantProduct101),
            $this->createProductPrice('each', $variantProduct102),
            $this->createProductPrice('set', $variantProduct102),
        ];

        $this->expectProductsAndPrices($products, $prices);

        $this->assertEquals(
            [
                'each' => ['price' => null, 'currency' => null, 'unit' => 'each', 'quantity' => null],
            ],
            $this->provider->getByProduct($configurableProduct100)
        );
    }

    public function testGetVariantsPricesByProductConfigurable()
    {
        $configurableProduct100 = $this->createProduct(100, Product::TYPE_CONFIGURABLE);
        $variantProduct101 = $this->createProduct(101, Product::TYPE_SIMPLE);
        $variantProduct102 = $this->createProduct(102, Product::TYPE_SIMPLE);

        $products = [
            100 => [
                'product' => $configurableProduct100,
                'variants' => [
                    101 => $variantProduct101,
                    102 => $variantProduct102,
                ],
            ],
        ];

        $prices = [
            $this->createProductPrice('each', $configurableProduct100),
            $this->createProductPrice('set', $configurableProduct100),
            $this->createProductPrice('each', $variantProduct101),
            $this->createProductPrice('set', $variantProduct101),
            $this->createProductPrice('each', $variantProduct102),
            $this->createProductPrice('set', $variantProduct102),
        ];

        $this->expectProductsAndPrices($products, $prices);

        $this->assertEquals(
            [
                101 => [
                    'each' => ['price' => null, 'currency' => null, 'unit' => 'each', 'quantity' => null],
                ],
                102 => [
                    'each' => ['price' => null, 'currency' => null, 'unit' => 'each', 'quantity' => null],
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

        $products = [
            1 => [
                'product' => $simpleProduct1,
                'variants' => [],
            ],
            100 => [
                'product' => $configurableProduct100,
                'variants' => [
                    101 => $variantProduct101,
                    102 => $variantProduct102,
                ],
            ],
        ];

        $prices = [
            $this->createProductPrice('each', $simpleProduct1),
            $this->createProductPrice('set', $simpleProduct1),
            $this->createProductPrice('each', $configurableProduct100),
            $this->createProductPrice('set', $configurableProduct100),
            $this->createProductPrice('each', $variantProduct101),
            $this->createProductPrice('set', $variantProduct101),
            $this->createProductPrice('each', $variantProduct102),
            $this->createProductPrice('set', $variantProduct102),
        ];

        $this->expectProductsAndPrices($products, $prices);

        $this->assertEquals(
            [
                1 => [
                    'each' => ['price' => null, 'currency' => null, 'unit' => 'each', 'quantity' => null],
                ],
                100 => [
                    'each' => ['price' => null, 'currency' => null, 'unit' => 'each', 'quantity' => null],
                ],
                101 => [
                    'each' => ['price' => null, 'currency' => null, 'unit' => 'each', 'quantity' => null],
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
     * @param string $unit
     * @param Product $product
     * @return CombinedProductPrice
     */
    private function createProductPrice($unit, $product)
    {
        $price = $this->getEntity(Price::class);

        $combinedProductPrice = new CombinedProductPrice();
        $combinedProductPrice->setProduct($product);
        $combinedProductPrice->setUnit($this->getUnit($unit));
        $combinedProductPrice->setPrice($price);

        return $combinedProductPrice;
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
                'products' => [
                    1 => [
                        'product' => $configurableProduct1,
                        'variants' => [
                            101 => $variant101,
                            102 => $variant102,
                        ],
                    ]
                ],
                'prices' =>[
                    $this->createProductPrice('each', $configurableProduct1),
                    $this->createProductPrice('each', $variant101),
                    $this->createProductPrice('each', $variant102),
                ],
                'expected' => true,
            ],
            'configurable product without prices' => [
                'products' => [
                    1 => [
                        'product' => $configurableProduct1,
                        'variants' => [
                            101 => $variant101,
                            102 => $variant102,
                        ],
                    ]
                ],
                'prices' =>[
                    $this->createProductPrice('each', $variant101),
                    $this->createProductPrice('each', $variant102),
                ],
                'expected' => false,
            ],
            'simple product with prices' => [
                'products' => [
                    1 => [
                        'product' => $simpleProduct2,
                        'variants' => [],
                    ]
                ],
                'prices' =>[
                    $this->createProductPrice('each', $simpleProduct2),
                ],
                'expected' => true,
            ],
            'simple product without prices' => [
                'products' => [
                    1 => [
                        'product' => $simpleProduct2,
                        'variants' => [],
                    ]
                ],
                'prices' =>[],
                'expected' => true,
            ],
        ];
    }

    /**
     * @param array $products
     * @param array $prices
     * @param bool $expected
     * @dataProvider isPriceBlockVisibleByProductDataProvider
     */
    public function testIsShowProductPriceContainer(array $products, array $prices, bool $expected)
    {
        $this->expectProductsAndPrices($products, $prices);

        $this->assertEquals($expected, $this->provider->isShowProductPriceContainer($products[1]['product']));
    }

    private function expectProductsAndPrices(array $products, array $prices)
    {
        $productIds = [];

        $parentProducts = [];
        $variants = [];

        foreach ($products as $product) {
            if ($product['product']->getType() === Product::TYPE_CONFIGURABLE) {
                $parentProducts[] = [$product['product']];
                $variants[] = $product['variants'];

                $productIds[] = $product['product']->getId();

                foreach ($product['variants'] as $variant) {
                    $productIds[] = $variant->getId();
                }
            } else {
                $productIds[] = $product['product']->getId();
            }
        }

        $this->productVariantAvailabilityProvider->expects($this->any())
            ->method('getSimpleProductsByVariantFields')
            ->withConsecutive(...$parentProducts)
            ->willReturnOnConsecutiveCalls(...$variants);

        $priceList = $this->getEntity(PriceList::class, ['id' => 23]);
        $this->priceListRequestHandler->expects($this->any())
            ->method('getPriceListByCustomer')
            ->willReturn($priceList);

        $this->userCurrencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn('EUR');

        $repo = $this->createMock(ProductPriceRepository::class);
        $repo->expects($this->any())
            ->method('findByPriceListIdAndProductIds')
            ->with(
                $this->shardManager,
                $priceList->getId(),
                $productIds,
                true,
                'EUR',
                null,
                ['unit' => 'ASC', 'currency' => 'DESC', 'quantity' => 'ASC']
            )
            ->willReturn($prices);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('OroPricingBundle:CombinedProductPrice')
            ->willReturn($repo);

        $this->productPriceFormatter->expects($this->any())
            ->method('formatProducts')
            ->willReturnCallback(function ($productsPrices) {
                $formattedProductsPrices = [];
                foreach ($productsPrices as $productId => $productsPrice) {
                    foreach ($productsPrice as $unit => $unitPrices) {
                        foreach ($unitPrices as $unitPrice) {
                            $formattedProductsPrices[$productId][sprintf(
                                '%s%s',
                                $unit,
                                $unitPrice['quantity']
                            )] = $unitPrice;
                        }
                    }
                }
                return $formattedProductsPrices;
            });
    }
}
