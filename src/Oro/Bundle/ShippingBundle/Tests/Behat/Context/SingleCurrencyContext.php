<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class SingleCurrencyContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given There is EUR currency in the system configuration
     */
    public function thereIsEurCurrencyInTheSystemConfiguration()
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_currency.default_currency', 'EUR');
        $configManager->flush();
    }

    /**
     * @Given Currency is set to USD
     */
    public function currencyIsSetToUsd()
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_currency.default_currency', 'USD');
        $configManager->flush();
    }

    /**
     * @Given Currency is set to EUR
     */
    public function currencyIsSetToEur()
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_currency.default_currency', 'EUR');
        $configManager->flush();
    }
}
