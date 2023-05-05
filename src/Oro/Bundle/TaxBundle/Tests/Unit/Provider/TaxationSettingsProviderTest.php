<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxationSettingsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TaxBaseExclusionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $taxBaseExclusionFactory;

    /** @var AddressModelFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $addressModelFactory;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var TaxationSettingsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->taxBaseExclusionFactory = $this->createMock(TaxBaseExclusionFactory::class);
        $this->addressModelFactory = $this->createMock(AddressModelFactory::class);
        $this->cacheProvider = $this->createMock(CacheInterface::class);

        $this->provider = new TaxationSettingsProvider(
            $this->configManager,
            $this->taxBaseExclusionFactory,
            $this->addressModelFactory,
            $this->cacheProvider
        );
    }

    private function configureGetCachedExpectations(string $optionKey, mixed $optionValue, string $methodName): void
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey(
            TaxationSettingsProvider::class . '::' . $methodName
        );

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($optionKey)
            ->willReturn($optionValue);

        $this->cacheProvider->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });
    }

    public function testGetStartCalculationWith()
    {
        $this->configureGetCachedExpectations(
            'oro_tax.start_calculation_with',
            TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE,
            'getStartCalculationWith'
        );

        $this->assertEquals(
            TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE,
            $this->provider->getStartCalculationWith()
        );
    }

    /**
     * @dataProvider isStartCalculationWithUnitPriceProvider
     */
    public function testIsStartCalculationWithUnitPrice(string $configValue, bool $expected)
    {
        $this->configureGetCachedExpectations(
            'oro_tax.start_calculation_with',
            $configValue,
            'getStartCalculationWith'
        );

        $this->assertEquals($expected, $this->provider->isStartCalculationWithUnitPrice());
    }

    public function isStartCalculationWithUnitPriceProvider(): array
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE,
                'expected' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_ROW_TOTAL,
                'expected' => false,
            ],
        ];
    }

    public function testGetStartCalculationOn()
    {
        $this->configureGetCachedExpectations(
            'oro_tax.start_calculation_on',
            TaxationSettingsProvider::START_CALCULATION_ON_TOTAL,
            'getStartCalculationOn'
        );

        $this->assertEquals(
            TaxationSettingsProvider::START_CALCULATION_ON_TOTAL,
            $this->provider->getStartCalculationOn()
        );
    }

    /**
     * @dataProvider isStartCalculationOnUnitPriceProvider
     */
    public function testIsStartCalculationOnUnitPrice(string $configValue, bool $expected)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_tax.start_calculation_on')
            ->willReturn($configValue);
        $saveCallback = function ($cacheKey, $callback) {
            $item = $this->createMock(ItemInterface::class);
            return $callback($item);
        };
        $this->cacheProvider->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(new ReturnCallback($saveCallback), $configValue);

        $this->assertEquals($expected, $this->provider->isStartCalculationOnTotal());
        $this->assertEquals(!$expected, $this->provider->isStartCalculationOnItem());
    }

    public function isStartCalculationOnUnitPriceProvider(): array
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_ON_TOTAL,
                'expected' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_ON_ITEM,
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider isStartCalculationWithRowTotalProvider
     */
    public function testIsStartCalculationWithRowTotal(string $configValue, bool $expected)
    {
        $this->configureGetCachedExpectations(
            'oro_tax.start_calculation_with',
            $configValue,
            'getStartCalculationWith'
        );

        $this->assertEquals($expected, $this->provider->isStartCalculationWithRowTotal());
    }

    public function isStartCalculationWithRowTotalProvider(): array
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE,
                'expected' => false,
            ],
            [
                'configValue' => TaxationSettingsProvider::START_CALCULATION_ROW_TOTAL,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider isProductPricesIncludeTaxProvider
     */
    public function testIsProductPricesIncludeTax(bool $configValue, bool $expected)
    {
        $this->configureGetCachedExpectations(
            'oro_tax.product_prices_include_tax',
            $configValue,
            'isProductPricesIncludeTax'
        );

        $this->assertEquals($expected, $this->provider->isProductPricesIncludeTax());
    }

    public function isProductPricesIncludeTaxProvider(): array
    {
        return [
            [
                true,
                true,
            ],
            [
                false,
                false,
            ],
        ];
    }

    public function testGetDestination()
    {
        $value = 'value';

        $this->configureGetCachedExpectations(
            'oro_tax.destination',
            $value,
            'getDestination'
        );

        $this->assertEquals($value, $this->provider->getDestination());
    }

    public function testGetDigitalProductsTaxCodesUS()
    {
        $value = ['AAAA', 'BBBB'];

        $this->configureGetCachedExpectations(
            'oro_tax.digital_products_us',
            $value,
            'getDigitalProductsTaxCodesUS'
        );

        $this->assertEquals($value, $this->provider->getDigitalProductsTaxCodesUS());
    }

    public function testGetDigitalProductsTaxCodesEU()
    {
        $value = ['AAAA', 'BBBB'];

        $this->configureGetCachedExpectations(
            'oro_tax.digital_products_eu',
            $value,
            'getDigitalProductsTaxCodesEU'
        );

        $this->assertEquals($value, $this->provider->getDigitalProductsTaxCodesEU());
    }

    /**
     * @dataProvider isBillingAddressDestinationProvider
     */
    public function testIsBillingAddressDestination(string $configValue, bool $expected)
    {
        $this->configureGetCachedExpectations(
            'oro_tax.destination',
            $configValue,
            'getDestination'
        );

        $this->assertEquals($expected, $this->provider->isBillingAddressDestination());
    }

    public function isBillingAddressDestinationProvider(): array
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                'value' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                'value' => false,
            ],
        ];
    }

    /**
     * @dataProvider isShippingAddressDestinationProvider
     */
    public function testIsShippingAddressDestination(string $configValue, bool $expected)
    {
        $this->configureGetCachedExpectations(
            'oro_tax.destination',
            $configValue,
            'getDestination'
        );

        $this->assertEquals($expected, $this->provider->isShippingAddressDestination());
    }

    public function isShippingAddressDestinationProvider(): array
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                'value' => false,
            ],
            [
                'configValue' => TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                'value' => true,
            ],
        ];
    }

    public function testGetBaseByDefaultAddressType()
    {
        $value = TaxationSettingsProvider::USE_AS_BASE_DESTINATION;

        $this->configureGetCachedExpectations(
            'oro_tax.use_as_base_by_default',
            $value,
            'getBaseByDefaultAddressType'
        );

        $this->assertEquals($value, $this->provider->getBaseByDefaultAddressType());
    }

    /**
     * @dataProvider isOriginBaseByDefaultAddressTypeProvider
     */
    public function testIsOriginBaseByDefaultAddressType(string $configValue, bool $expected)
    {
        $this->configureGetCachedExpectations(
            'oro_tax.use_as_base_by_default',
            $configValue,
            'getBaseByDefaultAddressType'
        );

        $this->assertEquals($expected, $this->provider->isOriginBaseByDefaultAddressType());
    }

    public function isOriginBaseByDefaultAddressTypeProvider(): array
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                'value' => false,
            ],
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_ORIGIN,
                'value' => true,
            ],
        ];
    }

    /**
     * @dataProvider isDestinationBaseByDefaultAddressTypeProvider
     */
    public function testIsDestinationBaseByDefaultAddressType(string $configValue, bool $expected)
    {
        $this->configureGetCachedExpectations(
            'oro_tax.use_as_base_by_default',
            $configValue,
            'getBaseByDefaultAddressType'
        );

        $this->assertEquals($expected, $this->provider->isDestinationBaseByDefaultAddressType());
    }

    public function isDestinationBaseByDefaultAddressTypeProvider(): array
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                'value' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_ORIGIN,
                'value' => false,
            ],
        ];
    }

    public function testGetBaseAddressExclusions()
    {
        $exclusionData = [
            ['data'],
            ['data'],
        ];

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_tax.use_as_base_exclusions')
            ->willReturn($exclusionData);

        $this->taxBaseExclusionFactory->expects($this->exactly(count($exclusionData)))
            ->method('create')
            ->willReturn(new TaxBaseExclusion());

        $this->assertCount(count($exclusionData), $this->provider->getBaseAddressExclusions());
    }

    public function testGetOrigin()
    {
        $addressData = ['address data'];

        $this->configureGetCachedExpectations(
            'oro_tax.origin_address',
            $addressData,
            'getOrigin'
        );

        $address = new Address();
        $this->addressModelFactory->expects($this->once())
            ->method('create')
            ->with($addressData)
            ->willReturn($address);

        $this->assertEquals($address, $this->provider->getOrigin());
    }

    public function testIsEnabled(): void
    {
        $this->mockSettingsCache(TaxationSettingsProvider::class . '::isEnabled', true);

        self::assertTrue($this->provider->isEnabled());
        self::assertFalse($this->provider->isDisabled());
    }

    public function testGetShippingTaxCodes()
    {
        $value = ['AAAA', 'BBBB'];

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_tax.shipping_tax_code')
            ->willReturn($value);

        $this->assertEquals($value, $this->provider->getShippingTaxCodes());
    }

    public function testIsShippingRatesIncludeTax()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_tax.shipping_rates_include_tax')
            ->willReturn(true);

        $this->assertTrue($this->provider->isShippingRatesIncludeTax());
    }

    /**
     * @dataProvider getIsCalculateAfterPromotionsEnabledDataProvider
     */
    public function testIsCalculateAfterPromotionsEnabled(bool $isEnabled, bool $expected): void
    {
        $this->mockSettingsCache(TaxationSettingsProvider::class . '::isCalculateAfterPromotionsEnabled', $isEnabled);

        self::assertEquals($expected, $this->provider->isCalculateAfterPromotionsEnabled());
        // check cache
        self::assertEquals($expected, $this->provider->isCalculateAfterPromotionsEnabled());
    }

    public function getIsCalculateAfterPromotionsEnabledDataProvider(): array
    {
        return [
            ['isEnabled' => false, 'expected' => false],
            ['isEnabled' => true, 'expected' => true],
        ];
    }

    private function mockSettingsCache(string $cacheKey, mixed $value): void
    {
        $this->cacheProvider->expects(self::exactly(2))
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey($cacheKey))
            ->willReturn($value);
    }
}
