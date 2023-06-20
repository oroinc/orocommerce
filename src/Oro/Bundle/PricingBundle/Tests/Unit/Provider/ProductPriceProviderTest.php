<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Tests\Unit\Provider\MemoryCacheProviderAwareTestTrait;
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
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductPriceProviderTest extends TestCase
{
    use EntityTrait;
    use MemoryCacheProviderAwareTestTrait;

    private const TEST_CURRENCY = 'USD';

    private ProductPriceStorageInterface|MockObject $priceStorage;

    private UserCurrencyManager|MockObject $currencyManager;

    private ProductPriceCriteriaDataExtractorInterface|MockObject $productPriceCriteriaDataExtractor;

    private ProductPriceByMatchingCriteriaProviderInterface|MockObject $priceByMatchingCriteriaProvider;

    private ProductPriceProvider $provider;

    private ?ProductPriceScopeCriteriaInterface $productPriceScopeCriteria = null;

    protected function setUp(): void
    {
        $this->priceStorage = $this->createMock(ProductPriceStorageInterface::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->productPriceCriteriaDataExtractor = $this->createMock(ProductPriceCriteriaDataExtractorInterface::class);
        $this->priceByMatchingCriteriaProvider = $this->createMock(
            ProductPriceByMatchingCriteriaProviderInterface::class
        );

        $this->provider = new ProductPriceProvider($this->priceStorage, $this->currencyManager);
        $this->provider->setProductPriceCriteriaDataExtractor($this->productPriceCriteriaDataExtractor);
        $this->provider->setPriceByMatchingCriteriaProvider($this->priceByMatchingCriteriaProvider);
    }

    /**
     * @dataProvider getSupportedCurrenciesProvider
     */
    public function testGetSupportedCurrencies(
        array $availableCurrencies,
        array $supportedCurrencies,
        array $expectedResult
    ): void {
        $this->currencyManager->expects(self::once())
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        $result = $this->provider->getSupportedCurrencies($scopeCriteria);

        self::assertEquals($expectedResult, $result);
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
        $this->currencyManager
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        $productUnitCodes = $unitCode ? [$unitCode] : null;
        $this->priceStorage->expects(self::once())
            ->method('getPrices')
            ->with($scopeCriteria, [1 => 1], $productUnitCodes, $finalCurrencies)
            ->willReturn($prices);

        $result = $this->provider->getPricesByScopeCriteriaAndProducts(
            $scopeCriteria,
            $products,
            $currencies,
            $unitCode
        );

        self::assertEquals($expectedResult, $result);
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
                'products' => [$this->getEntity(Product::class, ['id' => 1])],
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
                'products' => [$this->getEntity(Product::class, ['id' => 1])],
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

    public function testGetPricesByScopeCriteriaAndProductsWhenCache(): void
    {
        $currencies = [self::TEST_CURRENCY];
        $prices = $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['sample_unit']);

        $this->currencyManager
            ->method('getAvailableCurrencies')
            ->willReturn($currencies);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($currencies);

        $this->priceStorage->expects(self::never())
            ->method('getPrices');

        $this->mockMemoryCacheProvider($prices);
        $this->setMemoryCacheProvider($this->provider);

        $result = $this->provider->getPricesByScopeCriteriaAndProducts(
            $this->getProductPriceScopeCriteria(),
            [$this->getEntity(Product::class, ['id' => 1])],
            $currencies,
            'sample_unit'
        );

        self::assertEquals([1 => $prices], $result);
    }

    public function testGetMatchedPricesWhenCache(): void
    {
        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $productPriceCriteria = [$this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY)];
        $currencies = [self::TEST_CURRENCY];
        $prices = [$this->createPrice(15, self::TEST_CURRENCY, 10, 'item')];
        $expectedPrices = ['1-item-10-USD' => Price::create(15, self::TEST_CURRENCY)];

        $this->currencyManager
            ->method('getAvailableCurrencies')
            ->willReturn($currencies);

        $this->priceStorage->expects(self::once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($currencies);

        $this->productPriceCriteriaDataExtractor
            ->expects(self::once())
            ->method('extractCriteriaData')
            ->with($productPriceCriteria[0])
            ->willReturn(
                [
                    ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS => [1],
                    ProductPriceCriteriaDataExtractorInterface::UNIT_CODES => ['item'],
                    ProductPriceCriteriaDataExtractorInterface::CURRENCIES => [self::TEST_CURRENCY],
                ]
            );

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceMatchingCriteria')
            ->with($productPriceCriteria[0], new ProductPriceCollectionDTO($prices))
            ->willReturn($prices[0]);

        $this->priceStorage->expects(self::never())
            ->method('getPrices');

        $this->mockMemoryCacheProvider($prices);
        $this->setMemoryCacheProvider($this->provider);

        $result = $this->provider->getMatchedPrices($productPriceCriteria, $scopeCriteria);

        self::assertEquals($expectedPrices, $result);
    }

    public function testGetMatchedPrices(): void
    {
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);
        $prices = [
            $this->createPrice(10, self::TEST_CURRENCY, 10, 'item'),
            $this->createPrice(15, self::TEST_CURRENCY, 5, 'item'),
        ];

        $this->currencyManager
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor
            ->expects(self::once())
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

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
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

        $result = $this->provider->getMatchedPrices([$productPriceCriteria], $scopeCriteria);

        self::assertEquals([$productPriceCriteria->getIdentifier() => $prices[0]->getPrice()], $result);
    }

    public function testGetMatchedPricesWhenNoPrices(): void
    {
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);

        $this->currencyManager
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor
            ->expects(self::once())
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

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
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

        $result = $this->provider->getMatchedPrices([$productPriceCriteria], $scopeCriteria);

        self::assertEquals([$productPriceCriteria->getIdentifier() => null], $result);
    }

    public function testGetMatchedPricesWhenNoProductPriceByMatchingCriteriaProvider(): void
    {
        $itemUnitCode = 'item';
        $productId = 1;
        $productPriceCriteria = $this->getProductPriceCriteria($productId, $itemUnitCode, 10, self::TEST_CURRENCY);
        $prices = [
            $this->createPrice(10, self::TEST_CURRENCY, 10, $itemUnitCode),
            $this->createPrice(15, self::TEST_CURRENCY, 5, $itemUnitCode),
        ];

        $this->currencyManager
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->priceStorage->expects(self::once())
            ->method('getPrices')
            ->with(
                $scopeCriteria,
                [$productId => $productId],
                [$itemUnitCode => $itemUnitCode],
                [self::TEST_CURRENCY => self::TEST_CURRENCY]
            )
            ->willReturn($prices);

        $result = (new ProductPriceProvider($this->priceStorage, $this->currencyManager))
            ->getMatchedPrices([$productPriceCriteria], $scopeCriteria);

        self::assertEquals([$productPriceCriteria->getIdentifier() => $prices[0]->getPrice()], $result);
    }

    public function testGetMatchedPricesWhenMemoryCacheProvider(): void
    {
        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($this->provider);

        $this->testGetMatchedPrices();
    }

    public function testGetMatchedPricesWhenMemoryCacheProviderWhenNoPrices(): void
    {
        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($this->provider);

        $this->testGetMatchedPricesWhenNoPrices();
    }

    public function testGetMatchedProductPrices(): void
    {
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);
        $prices = [
            $this->createPrice(10, self::TEST_CURRENCY, 10, 'item'),
            $this->createPrice(15, self::TEST_CURRENCY, 5, 'item'),
        ];

        $this->currencyManager
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor
            ->expects(self::once())
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

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
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

        $result = $this->provider->getMatchedProductPrices([$productPriceCriteria], $scopeCriteria);

        self::assertEquals([$productPriceCriteria->getIdentifier() => $prices[0]], $result);
    }

    public function testGetMatchedProductPricesWhenNoPrices(): void
    {
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);

        $this->currencyManager
            ->method('getAvailableCurrencies')
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn([self::TEST_CURRENCY, 'UAH']);

        $this->productPriceCriteriaDataExtractor
            ->expects(self::once())
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

        $this->priceByMatchingCriteriaProvider
            ->expects(self::once())
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

        $result = $this->provider->getMatchedProductPrices([$productPriceCriteria], $scopeCriteria);

        self::assertEquals([$productPriceCriteria->getIdentifier() => null], $result);
    }

    public function testGetMatchedProductPricesWhenNoProductPriceByMatchingCriteriaProvider(): void
    {
        $productPriceCriteria = $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY);
        $scopeCriteria = $this->getProductPriceScopeCriteria();

        $this->priceStorage->expects(self::never())
            ->method('getPrices');

        $result = (new ProductPriceProvider($this->priceStorage, $this->currencyManager))
            ->getMatchedProductPrices([$productPriceCriteria], $scopeCriteria);

        self::assertEquals([], $result);
    }

    private function getProductPriceCriteria(
        int $productId,
        string $unitCode,
        int $quantity,
        string $currency,
        int $unitDefaultPrecision = 1
    ): ProductPriceCriteria {
        $productUnit = new ProductUnit();
        $productUnit
            ->setCode($unitCode)
            ->setDefaultPrecision($unitDefaultPrecision);
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        return new ProductPriceCriteria(
            $product,
            $productUnit,
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
            $this->getEntity(Product::class, ['id' => 1]),
            Price::create($price, $currency),
            $quantity,
            $this->getEntity(ProductUnit::class, ['code' => $unitCode])
        );
    }

    private function getProductPriceScopeCriteria(): ProductPriceScopeCriteriaInterface
    {
        if (null !== $this->productPriceScopeCriteria) {
            return $this->productPriceScopeCriteria;
        }

        $this->productPriceScopeCriteria = new ProductPriceScopeCriteria();
        $this->productPriceScopeCriteria->setCustomer($this->getEntity(Customer::class, ['id' => 1]));
        $this->productPriceScopeCriteria->setWebsite($this->getEntity(Website::class, ['id' => 1]));

        return $this->productPriceScopeCriteria;
    }
}
