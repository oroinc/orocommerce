<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;

class TaxationSettingsProvider
{
    const DESTINATION_BILLING_ADDRESS = 'billing_address';
    const DESTINATION_SHIPPING_ADDRESS = 'shipping_address';

    const START_CALCULATION_UNIT_PRICE = 'unit_price';
    const START_CALCULATION_ROW_TOTAL = 'row_total';

    const USE_AS_BASE_SHIPPING_ORIGIN = 'shipping_origin';
    const USE_AS_BASE_DESTINATION = 'destination';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return string
     */
    public function getStartCalculationWith()
    {
        return $this->configManager->get('orob2b_tax.start_calculation_with');
    }

    /**
     * @return string
     */
    public function isStartCalculationWithUnitPrice()
    {
        return $this->getStartCalculationWith() === self::START_CALCULATION_UNIT_PRICE;
    }

    /**
     * @return string
     */
    public function isStartCalculationWithRowTotal()
    {
        return $this->getStartCalculationWith() === self::START_CALCULATION_ROW_TOTAL;
    }

    /**
     * @return array
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
    public function getDigitalProductsTaxCodesUs()
    {
        return (array)$this->configManager->get('orob2b_tax.digital_products_us');
    }

    /**
     * @return array
     */
    public function getDigitalProductsTaxCodesEu()
    {
        return (array)$this->configManager->get('orob2b_tax.digital_products_eu');
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
        $exclusionsData = (array)$this->configManager->get('orob2b_tax.use_as_base_exclusions');

        $exclusions = [];
        foreach ($exclusionsData as $exclusion) {
            // TODO: We should transform this to entities
            $exclusions[] = new TaxBaseExclusion($exclusion);
        }

        return $exclusions;
    }

    /**
     * @return \Oro\Bundle\AddressBundle\Entity\AbstractAddress
     */
    public function getOrigin()
    {
        /** @todo: add address form to config */
        return new Address();
    }
}
