<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;
use Oro\Bundle\TaxBundle\Model\Address;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides taxation setting needed for tax calculation and work with
 * cached results of config manger of most frequent calls
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxationSettingsProvider
{
    public const DESTINATION_BILLING_ADDRESS = 'billing_address';
    public const DESTINATION_SHIPPING_ADDRESS = 'shipping_address';

    public const START_CALCULATION_UNIT_PRICE = 'unit_price';
    public const START_CALCULATION_ROW_TOTAL = 'row_total';

    public const START_CALCULATION_ON_TOTAL = 'total';
    public const START_CALCULATION_ON_ITEM = 'item';

    public const USE_AS_BASE_ORIGIN = 'origin';
    public const USE_AS_BASE_DESTINATION = 'destination';

    public const SCALE = 2;

    /**
     * For scale = 2 we use 3rd number to scale and fourth position to divide
     */
    public const CALCULATION_SCALE = 6;
    public const CALCULATION_SCALE_AS_PERCENT = self::CALCULATION_SCALE - 2;

    protected ConfigManager $configManager;
    protected TaxBaseExclusionFactory $taxBaseExclusionFactory;
    private AddressModelFactory $addressModelFactory;
    private CacheInterface $cacheProvider;

    public function __construct(
        ConfigManager $configManager,
        TaxBaseExclusionFactory $taxBaseExclusionFactory,
        AddressModelFactory $addressModelFactory,
        CacheInterface $cacheProvider
    ) {
        $this->configManager = $configManager;
        $this->taxBaseExclusionFactory = $taxBaseExclusionFactory;
        $this->addressModelFactory = $addressModelFactory;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * Caches settings which dramatically decreases calls to ConfigManager::get method
     */
    private function getCached(string $cacheKey, string $settingKey): mixed
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($cacheKey);
        return $this->cacheProvider->get($cacheKey, function () use ($settingKey) {
            return $this->configManager->get($settingKey);
        });
    }

    public function isEnabled(): bool
    {
        return (bool) $this->getCached(__METHOD__, 'oro_tax.tax_enable');
    }

    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }

    public function getStartCalculationWith(): ?string
    {
        return $this->getCached(__METHOD__, 'oro_tax.start_calculation_with');
    }

    public function isStartCalculationWithUnitPrice(): bool
    {
        return $this->getStartCalculationWith() === self::START_CALCULATION_UNIT_PRICE;
    }

    public function isStartCalculationWithRowTotal(): bool
    {
        return $this->getStartCalculationWith() === self::START_CALCULATION_ROW_TOTAL;
    }

    public function getStartCalculationOn(): ?string
    {
        return $this->getCached(__METHOD__, 'oro_tax.start_calculation_on');
    }

    public function isStartCalculationOnTotal(): bool
    {
        return $this->getStartCalculationOn() === self::START_CALCULATION_ON_TOTAL;
    }

    public function isStartCalculationOnItem(): bool
    {
        return $this->getStartCalculationOn() === self::START_CALCULATION_ON_ITEM;
    }

    public function isProductPricesIncludeTax(): bool
    {
        return $this->getCached(__METHOD__, 'oro_tax.product_prices_include_tax');
    }

    public function getDestination(): ?string
    {
        return $this->getCached(__METHOD__, 'oro_tax.destination');
    }

    public function getDigitalProductsTaxCodesUS(): ?array
    {
        return $this->getCached(__METHOD__, 'oro_tax.digital_products_us');
    }

    public function getDigitalProductsTaxCodesEU(): ?array
    {
        return $this->getCached(__METHOD__, 'oro_tax.digital_products_eu');
    }

    public function isBillingAddressDestination(): bool
    {
        return $this->getDestination() === self::DESTINATION_BILLING_ADDRESS;
    }

    public function isShippingAddressDestination(): bool
    {
        return $this->getDestination() === self::DESTINATION_SHIPPING_ADDRESS;
    }

    public function getBaseByDefaultAddressType(): ?string
    {
        return $this->getCached(__METHOD__, 'oro_tax.use_as_base_by_default');
    }

    public function isOriginBaseByDefaultAddressType(): bool
    {
        return $this->getBaseByDefaultAddressType() === self::USE_AS_BASE_ORIGIN;
    }

    public function isDestinationBaseByDefaultAddressType(): bool
    {
        return $this->getBaseByDefaultAddressType() === self::USE_AS_BASE_DESTINATION;
    }

    public function getBaseAddressExclusions(): array
    {
        $exclusionsData = $this->configManager->get('oro_tax.use_as_base_exclusions');

        $exclusions = [];
        foreach ($exclusionsData as $exclusionData) {
            $exclusions[] = $this->taxBaseExclusionFactory->create($exclusionData);
        }

        return $exclusions;
    }

    public function getOrigin(): Address
    {
        $originAddressValues = $this->getCached(__METHOD__, 'oro_tax.origin_address');

        return $this->addressModelFactory->create($originAddressValues);
    }

    public function getShippingTaxCodes(): array
    {
        return (array)$this->configManager->get('oro_tax.shipping_tax_code');
    }

    public function isShippingRatesIncludeTax(): bool
    {
        return (bool)$this->configManager->get('oro_tax.shipping_rates_include_tax');
    }

    public function isCalculateAfterPromotionsEnabled(): bool
    {
        return (bool) $this->getCached(__METHOD__, 'oro_tax.calculate_taxes_after_promotions');
    }
}
