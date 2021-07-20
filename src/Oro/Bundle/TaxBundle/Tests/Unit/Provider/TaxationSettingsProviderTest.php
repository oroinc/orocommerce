<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxationSettingsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var TaxBaseExclusionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxBaseExclusionFactory;

    /**
     * @var AddressModelFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $addressModelFactory;

    /**
     * @var TaxationSettingsProvider
     */
    protected $provider;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->taxBaseExclusionFactory = $this->createMock(TaxBaseExclusionFactory::class);

        $this->addressModelFactory = $this->createMock(AddressModelFactory::class);

        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->provider = new TaxationSettingsProvider(
            $this->configManager,
            $this->taxBaseExclusionFactory,
            $this->addressModelFactory,
            $this->cacheProvider
        );
    }

    protected function tearDown(): void
    {
        unset($this->configManager, $this->taxBaseExclusionFactory, $this->addressModelFactory, $this->provider);
    }

    /**
     * @param string $optionKey
     * @param mixed $optionValue
     * @param string $methodName
     */
    private function configureGetCachedExpectations(
        $optionKey,
        $optionValue,
        $methodName
    ) {
        $cacheKey = TaxationSettingsProvider::class . '::' . $methodName;

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with($optionKey)
            ->willReturn($optionValue);

        $this->cacheProvider->expects($this->exactly(1))
            ->method('contains')
            ->with($cacheKey)
            ->willReturnOnConsecutiveCalls(false);

        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($cacheKey, $optionValue);
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
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsStartCalculationWithUnitPrice($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.start_calculation_with')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isStartCalculationWithUnitPrice());
    }

    /**
     * @return array
     */
    public function isStartCalculationWithUnitPriceProvider()
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
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsStartCalculationOnUnitPrice($configValue, $expected)
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with('oro_tax.start_calculation_on')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isStartCalculationOnTotal());
        $this->assertEquals(!$expected, $this->provider->isStartCalculationOnItem());
    }

    /**
     * @return array
     */
    public function isStartCalculationOnUnitPriceProvider()
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
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsStartCalculationWithRowTotal($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.start_calculation_with')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isStartCalculationWithRowTotal());
    }

    /**
     * @return array
     */
    public function isStartCalculationWithRowTotalProvider()
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
     * @param bool $configValue
     * @param bool $expected
     */
    public function testIsProductPricesIncludeTax($configValue, $expected)
    {
        $this->configureGetCachedExpectations(
            'oro_tax.product_prices_include_tax',
            $configValue,
            'isProductPricesIncludeTax'
        );

        $this->assertEquals($expected, $this->provider->isProductPricesIncludeTax());
    }

    /**
     * @return array
     */
    public function isProductPricesIncludeTaxProvider()
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
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsBillingAddressDestination($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.destination')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isBillingAddressDestination());
    }

    /**
     * @return array
     */
    public function isBillingAddressDestinationProvider()
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
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsShippingAddressDestination($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.destination')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isShippingAddressDestination());
    }

    /**
     * @return array
     */
    public function isShippingAddressDestinationProvider()
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
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsOriginBaseByDefaultAddressType($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.use_as_base_by_default')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isOriginBaseByDefaultAddressType());
    }

    /**
     * @return array
     */
    public function isOriginBaseByDefaultAddressTypeProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                'value' => false,
            ],
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                'value' => true,
            ],
        ];
    }

    /**
     * @dataProvider isDestinationBaseByDefaultAddressTypeProvider
     * @param string $configValue
     * @param bool $expected
     */
    public function testIsDestinationBaseByDefaultAddressType($configValue, $expected)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.use_as_base_by_default')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->provider->isDestinationBaseByDefaultAddressType());
    }

    /**
     * @return array
     */
    public function isDestinationBaseByDefaultAddressTypeProvider()
    {
        return [
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                'value' => true,
            ],
            [
                'configValue' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
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

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.use_as_base_exclusions')
            ->willReturn($exclusionData);

        $this->taxBaseExclusionFactory
            ->expects($this->exactly(count($exclusionData)))
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
        $this->addressModelFactory
            ->expects($this->once())
            ->method('create')
            ->with($addressData)
            ->willReturn($address);

        $this->assertEquals($address, $this->provider->getOrigin());
    }

    public function testIsEnabled(): void
    {
        $this->mockSettingsCache(
            TaxationSettingsProvider::class . '::isEnabled',
            'oro_tax.tax_enable',
            true
        );

        self::assertTrue($this->provider->isEnabled());
        self::assertFalse($this->provider->isDisabled());
    }

    public function testGetShippingTaxCodes()
    {
        $value = ['AAAA', 'BBBB'];

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.shipping_tax_code')
            ->willReturn($value);

        $this->assertEquals($value, $this->provider->getShippingTaxCodes());
    }

    public function testIsShippingRatesIncludeTax()
    {
        $this->configManager
            ->expects($this->once())
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
        $this->mockSettingsCache(
            TaxationSettingsProvider::class . '::isCalculateAfterPromotionsEnabled',
            'oro_tax.calculate_taxes_after_promotions',
            $isEnabled
        );

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

    /**
     * @param string $cacheKey
     * @param string $settingKey
     * @param mixed $value
     */
    private function mockSettingsCache(string $cacheKey, string $settingKey, $value): void
    {
        $this->cacheProvider->expects(self::exactly(2))
            ->method('contains')
            ->with($cacheKey)
            ->willReturnOnConsecutiveCalls(false, true);
        $this->cacheProvider->expects(self::once())
            ->method('save')
            ->with($cacheKey, $value);
        $this->cacheProvider->expects(self::once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn($value);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with($settingKey)
            ->willReturn($value);
    }
}
