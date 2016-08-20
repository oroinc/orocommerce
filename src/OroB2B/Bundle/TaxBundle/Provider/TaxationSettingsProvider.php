<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
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
    const CALCULATION_SCALE = 4;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var TaxBaseExclusionFactory
     */
    protected $taxBaseExclusionFactory;

    /**
     * @param ConfigManager $configManager
     * @param TaxBaseExclusionFactory $taxBaseExclusionFactory
     * @param AddressModelFactory $addressModelFactory
     */
    public function __construct(
        ConfigManager $configManager,
        TaxBaseExclusionFactory $taxBaseExclusionFactory,
        AddressModelFactory $addressModelFactory
    ) {
        $this->configManager = $configManager;
        $this->taxBaseExclusionFactory = $taxBaseExclusionFactory;
        $this->addressModelFactory = $addressModelFactory;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->configManager->get('orob2b_tax.tax_enable');
    }

    /**
     * @return string
     */
    public function getStartCalculationWith()
    {
        return $this->configManager->get('orob2b_tax.start_calculation_with');
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
        return $this->configManager->get('orob2b_tax.start_calculation_on');
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
        return $this->configManager->get('orob2b_tax.product_prices_include_tax');
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->configManager->get('orob2b_tax.destination');
    }

    /**
     * @return array
     */
    public function getDigitalProductsTaxCodesUS()
    {
        return $this->configManager->get('orob2b_tax.digital_products_us');
    }

    /**
     * @return array
     */
    public function getDigitalProductsTaxCodesEU()
    {
        return $this->configManager->get('orob2b_tax.digital_products_eu');
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
        return $this->configManager->get('orob2b_tax.use_as_base_by_default');
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
        $exclusionsData = $this->configManager->get('orob2b_tax.use_as_base_exclusions');

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
        $originAddressValues = $this->configManager->get('orob2b_tax.origin_address');

        return $this->addressModelFactory->create($originAddressValues);
    }
}
