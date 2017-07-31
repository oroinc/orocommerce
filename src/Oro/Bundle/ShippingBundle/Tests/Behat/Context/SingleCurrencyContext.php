<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class SingleCurrencyContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given There is :currency currency in the system configuration
     */
    public function thereIsEurCurrencyInTheSystemConfiguration($currency)
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_currency.default_currency', $currency);
        $configManager->flush();
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
