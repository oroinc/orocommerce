<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class SingleCurrencyContext extends OroFeatureContext
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @Given There is :currency currency in the system configuration
     */
    public function thereIsEurCurrencyInTheSystemConfiguration($currency)
    {
        $this->configManager->set('oro_currency.default_currency', $currency);
        $this->configManager->flush();
    }

    /**
     * @Given Currency is set to USD
     */
    public function currencyIsSetToUsd()
    {
        $this->thereIsEurCurrencyInTheSystemConfiguration('USD');
    }

    /**
     * @Given Currency is set to EUR
     */
    public function currencyIsSetToEur()
    {
        $this->thereIsEurCurrencyInTheSystemConfiguration('EUR');
    }
}
