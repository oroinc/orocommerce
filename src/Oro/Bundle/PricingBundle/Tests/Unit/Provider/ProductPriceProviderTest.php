<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractorInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProviderInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;

class ProductPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CURRENCY = 'USD';

    /** @var ProductPriceStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceStorage;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var ProductPriceCriteriaDataExtractorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceCriteriaDataExtractor;

    /** @var ProductPriceByMatchingCriteriaProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceByMatchingCriteriaProvider;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var ProductPriceProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->priceStorage = $this->createMock(ProductPriceStorageInterface::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->productPriceCriteriaDataExtractor = $this->createMock(ProductPriceCriteriaDataExtractorInterface::class);
        $this->priceByMatchingCriteriaProvider = $this->createMock(
            ProductPriceByMatchingCriteriaProviderInterface::class
        );
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $this->provider = new ProductPriceProvider(
            $this->priceStorage,
            $this->currencyManager,
            $this->productPriceCriteriaDataExtractor,
            $this->priceByMatchingCriteriaProvider,
            $this->memoryCacheProvider
        );
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductUnit(string $unitCode, int $unitDefaultPrecision = 1): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);
        $productUnit->setDefaultPrecision($unitDefaultPrecision);

        return $productUnit;
    }

    private function getProductPriceCriteria(
        int $productId,
        string $unitCode,
        int $quantity,
        string $currency,
        int $unitDefaultPrecision = 1
    ): ProductPriceCriteria {
        return new ProductPriceCriteria(
            $this->getProduct($productId),
            $this->getProductUnit($unitCode, $unitDefaultPrecision),
            $quantity,
            $currency
        );
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
            $this->getProduct(1),
            Price::create($price, $currency),
            $quantity,
            $this->getProductUnit($unitCode)
        );
    }

    private function getProductPriceScopeCriteria(): ProductPriceScopeCriteriaInterface
    {
        $customer = new Customer();
        ReflectionUtil::setId($customer, 1);

        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $productPriceScopeCriteria = new ProductPriceScopeCriteria();
        $productPriceScopeCriteria->setCustomer($customer);
        $productPriceScopeCriteria->setWebsite($website);

        return $productPriceScopeCriteria;
    }

    /**
     * @dataProvider getSupportedCurrenciesProvider
     */
    public function testGetSupportedCurrencies(
        array $availableCurrencies,
        array $supportedCurrencies,
        array $expectedResult
    ): void {
        $scopeCriteria = $this->getProductPriceScopeCriteria();

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        self::assertEquals($expectedResult, $this->provider->getSupportedCurrencies($scopeCriteria));
    }

    public function getSupportedCurrenciesProvider(): array
    {
        return [
            'one supported currency exists' => [
                'availableCurrencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'expectedResult' => [self::TEST_CURRENCY],
            ],
            'no available currencies' => [
                'availableCurrencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => ['EUR'],
                'expectedResult' => [],
            ],
        ];
    }

    /**
     * @dataProvider getPricesByScopeCriteriaAndProductsProvider
     */
    public function testGetPricesByScopeCriteriaAndProducts(
        array $currencies,
        array $supportedCurrencies,
        array $availableCurrencies,
        array $finalCurrencies,
        ?string $unitCode,
        array $products,
        array $prices,
        array $expectedResult
    ): void {
        $scopeCriteria = $this->getProductPriceScopeCriteria();

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        $productUnitCodes = $unitCode ? [$unitCode] : null;
        $this->priceStorage->expects(self::once())
            ->method('getPrices')
            ->with($scopeCriteria, [1 => 1], $productUnitCodes, $finalCurrencies)
            ->willReturn($prices);

        self::assertEquals(
            $expectedResult,
            $this->provider->getPricesByScopeCriteriaAndProducts($scopeCriteria, $products, $currencies, $unitCode)
        );
    }

    public function getPricesByScopeCriteriaAndProductsProvider(): array
    {
        return [
            'with allowed currency' => [
                'currencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'finalCurrencies' => [self::TEST_CURRENCY],
                'unitCode' => 'unit',
                'products' => [$this->getProduct(1)],
                'prices' => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit']),
                'expectedResult' => [
                    1 => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit']),
                ],
            ],
            'without unit code' => [
                'currencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'finalCurrencies' => [self::TEST_CURRENCY],
                'unitCode' => null,
                'products' => [$this->getProduct(1)],
                'prices' => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit']),
                'expectedResult' => [
                    1 => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit']),
                ],
            ],
            'with products as ids' => [
                'currencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'finalCurrencies' => [self::TEST_CURRENCY],
                'unitCode' => null,
                'products' => [1],
                'prices' => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit']),
                'expectedResult' => [
                    1 => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit']),
                ],
            ],
        ];
    }

    public function testGetMatchedPrices(): void
    {
        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);
        $prices = [
            $this->createPrice(10, self::TEST_CURRENCY, 10, 'item'),
            $this->createPrice(15, self::TEST_CURRENCY, 5, 'item'),
        ];

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->with(
                [
                    'product_price_scope_criteria' => $scopeCriteria,
                    [$productPriceCriteria->getProduct()->getId()],
                    [$productPriceCriteria->getCurrency()],
                    [$productPriceCriteria->getProductUnit()->getCode()],
                ]
            )
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor->expects(self::once())
            ->method('extractCriteriaData')
            ->with($productPriceCriteria)
            ->willReturn(
                [
                    ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [
                        $productPriceCriteria->getProduct()->getId(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [
                        $productPriceCriteria->getProductUnit()->getCode(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$productPriceCriteria->getCurrency()],
                ]
            );

        $this->priceByMatchingCriteriaProvider->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria, new ProductPriceCollectionDTO([$prices[1], $prices[0]]))
            ->willReturn($prices[0]);

        $this->priceStorage->expects(self::once())
            ->method('getPrices')
            ->with(
                $scopeCriteria,
                [$productPriceCriteria->getProduct()->getId()],
                [$productPriceCriteria->getProductUnit()->getCode()],
                [$productPriceCriteria->getCurrency()]
            )
            ->willReturn($prices);

        self::assertEquals(
            [$productPriceCriteria->getIdentifier() => $prices[0]->getPrice()],
            $this->provider->getMatchedPrices([$productPriceCriteria], $scopeCriteria)
        );
    }

    public function testGetMatchedPricesWhenPricesCached(): void
    {
        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);
        $prices = [
            $this->createPrice(15, self::TEST_CURRENCY, 5, 'item'),
            $this->createPrice(10, self::TEST_CURRENCY, 10, 'item'),
        ];

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->with(
                [
                    'product_price_scope_criteria' => $scopeCriteria,
                    [$productPriceCriteria->getProduct()->getId()],
                    [$productPriceCriteria->getCurrency()],
                    [$productPriceCriteria->getProductUnit()->getCode()],
                ]
            )
            ->willReturnCallback(function () use ($prices) {
                return $prices;
            });

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor->expects(self::once())
            ->method('extractCriteriaData')
            ->with($productPriceCriteria)
            ->willReturn(
                [
                    ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [
                        $productPriceCriteria->getProduct()->getId(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [
                        $productPriceCriteria->getProductUnit()->getCode(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$productPriceCriteria->getCurrency()],
                ]
            );

        $this->priceByMatchingCriteriaProvider->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria, new ProductPriceCollectionDTO($prices))
            ->willReturn($prices[0]);

        $this->priceStorage->expects(self::never())
            ->method('getPrices');

        self::assertEquals(
            [$productPriceCriteria->getIdentifier() => $prices[0]->getPrice()],
            $this->provider->getMatchedPrices([$productPriceCriteria], $scopeCriteria)
        );
    }

    public function testGetMatchedPricesWhenNoPrices(): void
    {
        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor->expects(self::once())
            ->method('extractCriteriaData')
            ->with($productPriceCriteria)
            ->willReturn(
                [
                    ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [
                        $productPriceCriteria->getProduct()->getId(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [
                        $productPriceCriteria->getProductUnit()->getCode(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$productPriceCriteria->getCurrency()],
                ]
            );

        $this->priceByMatchingCriteriaProvider->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria, new ProductPriceCollectionDTO([]))
            ->willReturn(null);

        $this->priceStorage->expects(self::once())
            ->method('getPrices')
            ->with(
                $scopeCriteria,
                [$productPriceCriteria->getProduct()->getId()],
                [$productPriceCriteria->getProductUnit()->getCode()],
                [$productPriceCriteria->getCurrency()]
            )
            ->willReturn([]);

        self::assertEquals(
            [$productPriceCriteria->getIdentifier() => null],
            $this->provider->getMatchedPrices([$productPriceCriteria], $scopeCriteria)
        );
    }

    public function testGetMatchedProductPrices(): void
    {
        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);
        $prices = [
            $this->createPrice(10, self::TEST_CURRENCY, 10, 'item'),
            $this->createPrice(15, self::TEST_CURRENCY, 5, 'item'),
        ];

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor->expects(self::once())
            ->method('extractCriteriaData')
            ->with($productPriceCriteria)
            ->willReturn(
                [
                    ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [
                        $productPriceCriteria->getProduct()->getId(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [
                        $productPriceCriteria->getProductUnit()->getCode(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$productPriceCriteria->getCurrency()],
                ]
            );

        $this->priceByMatchingCriteriaProvider->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria, new ProductPriceCollectionDTO([$prices[1], $prices[0]]))
            ->willReturn($prices[0]);

        $this->priceStorage->expects(self::once())
            ->method('getPrices')
            ->with(
                $scopeCriteria,
                [$productPriceCriteria->getProduct()->getId()],
                [$productPriceCriteria->getProductUnit()->getCode()],
                [$productPriceCriteria->getCurrency()]
            )
            ->willReturn($prices);

        self::assertEquals(
            [$productPriceCriteria->getIdentifier() => $prices[0]],
            $this->provider->getMatchedProductPrices([$productPriceCriteria], $scopeCriteria)
        );
    }

    public function testGetMatchedProductPricesWhenNoPrices(): void
    {
        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor->expects(self::once())
            ->method('extractCriteriaData')
            ->with($productPriceCriteria)
            ->willReturn(
                [
                    ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [
                        $productPriceCriteria->getProduct()->getId(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => [
                        $productPriceCriteria->getProductUnit()->getCode(),
                    ],
                    ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [$productPriceCriteria->getCurrency()],
                ]
            );

        $this->priceByMatchingCriteriaProvider->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria, new ProductPriceCollectionDTO([]))
            ->willReturn(null);

        $this->priceStorage->expects(self::once())
            ->method('getPrices')
            ->with(
                $scopeCriteria,
                [$productPriceCriteria->getProduct()->getId()],
                [$productPriceCriteria->getProductUnit()->getCode()],
                [$productPriceCriteria->getCurrency()]
            )
            ->willReturn([]);

        self::assertEquals(
            [$productPriceCriteria->getIdentifier() => null],
            $this->provider->getMatchedProductPrices([$productPriceCriteria], $scopeCriteria)
        );
    }
}
