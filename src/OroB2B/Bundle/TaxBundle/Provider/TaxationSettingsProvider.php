<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class TaxationSettingsProvider
{
    const DESTINATION_BILLING_ADDRESS = 'billing_address';
    const DESTINATION_SHIPPING_ADDRESS = 'shipping_address';

    const START_CALCULATION_UNIT_PRICE = 'unit_price';
    const START_CALCULATION_ROW_TOTAL = 'row_total';

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
     * @return array
     */
    public function getShippingOriginAsBase()
    {
        return $this->configManager->get('orob2b_tax.shipping_origin_as_base');
    }

    /**
     * @return bool
     */
    public function getDestinationAsBase()
    {
        return $this->configManager->get('orob2b_tax.destination_as_base');
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
     * @return string
     */
    public function isBillingAddressDestination()
    {
        return $this->getDestination() === self::DESTINATION_BILLING_ADDRESS;
    }

    /**
     * @return string
     */
    public function isShippingAddressDestination()
    {
        return $this->getDestination() === self::DESTINATION_SHIPPING_ADDRESS;
    }

    /**
     * @return \Oro\Bundle\AddressBundle\Entity\AbstractAddress
     */
    public function getOrigin()
    {
        /** @todo: add address form to config */
    }
}
