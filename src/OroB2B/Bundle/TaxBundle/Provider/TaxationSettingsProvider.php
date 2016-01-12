<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class TaxationSettingsProvider
{
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
}
