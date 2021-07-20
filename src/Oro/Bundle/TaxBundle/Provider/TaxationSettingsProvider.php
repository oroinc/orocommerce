<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;

/**
 * Provides taxation setting needed for tax calculation and work with
 * cached results of config manger of most frequent calls
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxationSettingsProvider
{
    const DESTINATION_BILLING_ADDRESS = 'billing_address';
    const DESTINATION_SHIPPING_ADDRESS = 'shipping_address';

    const START_CALCULATION_UNIT_PRICE = 'unit_price';
    const START_CALCULATION_ROW_TOTAL = 'row_total';

    const START_CALCULATION_ON_TOTAL = 'total';
    const START_CALCULATION_ON_ITEM = 'item';

    const USE_AS_BASE_SHIPPING_ORIGIN = 'shipping_origin';
    const USE_AS_BASE_DESTINATION = 'destination';

    const SCALE = 2;

    /**
     * For scale = 2 we use 3rd number to scale and fourth position to divide
     */
    const CALCULATION_SCALE = 6;
    const CALCULATION_SCALE_AS_PERCENT = self::CALCULATION_SCALE - 2;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var TaxBaseExclusionFactory
     */
    protected $taxBaseExclusionFactory;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    public function __construct(
        ConfigManager $configManager,
        TaxBaseExclusionFactory $taxBaseExclusionFactory,
        AddressModelFactory $addressModelFactory,
        CacheProvider $cacheProvider
    ) {
        $this->configManager = $configManager;
        $this->taxBaseExclusionFactory = $taxBaseExclusionFactory;
        $this->addressModelFactory = $addressModelFactory;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * Caches settings which dramatically decreases calls to ConfigManager::get method
     * @param string $cacheKey
     * @param string $settingKey
     * @return mixed
     */
    private function getCached($cacheKey, $settingKey)
    {
        if (!$this->cacheProvider->contains($cacheKey)) {
            $configSetting = $this->configManager->get($settingKey);
            $this->cacheProvider->save($cacheKey, $configSetting);

            return $configSetting;
        }

        return $this->cacheProvider->fetch($cacheKey);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->getCached(__METHOD__, 'oro_tax.tax_enable');
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return !$this->isEnabled();
    }

    /**
     * @return string
     */
    public function getStartCalculationWith()
    {
        return $this->getCached(__METHOD__, 'oro_tax.start_calculation_with');
    }

    /**
     * @return bool
     */
    public function isStartCalculationWithUnitPrice()
    {
        return $this->getStartCalculationWith() === self::START_CALCULATION_UNIT_PRICE;
    }

    /**
     * @return bool
     */
    public function isStartCalculationWithRowTotal()
    {
        return $this->getStartCalculationWith() === self::START_CALCULATION_ROW_TOTAL;
    }

    /**
     * @return string
     */
    public function getStartCalculationOn()
    {
        return $this->getCached(__METHOD__, 'oro_tax.start_calculation_on');
    }

    /**
     * @return bool
     */
    public function isStartCalculationOnTotal()
    {
        return $this->getStartCalculationOn() === self::START_CALCULATION_ON_TOTAL;
    }

    /**
     * @return bool
     */
    public function isStartCalculationOnItem()
    {
        return $this->getStartCalculationOn() === self::START_CALCULATION_ON_ITEM;
    }

    /**
     * @return bool
     */
    public function isProductPricesIncludeTax()
    {
        return $this->getCached(__METHOD__, 'oro_tax.product_prices_include_tax');
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->getCached(__METHOD__, 'oro_tax.destination');
    }

    /**
     * @return array
     */
    public function getDigitalProductsTaxCodesUS()
    {
        return $this->getCached(__METHOD__, 'oro_tax.digital_products_us');
    }

    /**
     * @return array
     */
    public function getDigitalProductsTaxCodesEU()
    {
        return $this->getCached(__METHOD__, 'oro_tax.digital_products_eu');
    }

    /**
     * @return bool
     */
    public function isBillingAddressDestination()
    {
        return $this->getDestination() === self::DESTINATION_BILLING_ADDRESS;
    }

    /**
     * @return bool
     */
    public function isShippingAddressDestination()
    {
        return $this->getDestination() === self::DESTINATION_SHIPPING_ADDRESS;
    }

    /**
     * @return string
     */
    public function getBaseByDefaultAddressType()
    {
        return $this->getCached(__METHOD__, 'oro_tax.use_as_base_by_default');
    }

    /**
     * @return bool
     */
    public function isOriginBaseByDefaultAddressType()
    {
        return $this->getBaseByDefaultAddressType() === self::USE_AS_BASE_SHIPPING_ORIGIN;
    }

    /**
     * @return bool
     */
    public function isDestinationBaseByDefaultAddressType()
    {
        return $this->getBaseByDefaultAddressType() === self::USE_AS_BASE_DESTINATION;
    }

    /**
     * @return TaxBaseExclusion[]
     */
    public function getBaseAddressExclusions()
    {
        $exclusionsData = $this->configManager->get('oro_tax.use_as_base_exclusions');

        $exclusions = [];
        foreach ($exclusionsData as $exclusionData) {
            $exclusions[] = $this->taxBaseExclusionFactory->create($exclusionData);
        }

        return $exclusions;
    }

    /**
     * @return Address
     */
    public function getOrigin()
    {
        $originAddressValues = $this->getCached(__METHOD__, 'oro_tax.origin_address');

        return $this->addressModelFactory->create($originAddressValues);
    }

    /**
     * @return array
     */
    public function getShippingTaxCodes()
    {
        return (array)$this->configManager->get('oro_tax.shipping_tax_code');
    }

    /**
     * @return bool
     */
    public function isShippingRatesIncludeTax()
    {
        return (bool)$this->configManager->get('oro_tax.shipping_rates_include_tax');
    }

    public function isCalculateAfterPromotionsEnabled(): bool
    {
        return (bool) $this->getCached(__METHOD__, 'oro_tax.calculate_taxes_after_promotions');
    }
}
