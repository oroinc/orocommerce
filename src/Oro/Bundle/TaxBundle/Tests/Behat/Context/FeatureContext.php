<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Context;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @Given Base tax value is set to "Shipping Origin"
     */
    public function taxBaseIsDefaultShipping()
    {
        $this->thereIsUseAsBaseByDefaultInTheSystemConfiguration(TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN);
    }

    /**
     * @Given Base tax value is set to "Destination"
     */
    public function taxBaseIsDefaultDestination()
    {
        $this->thereIsUseAsBaseByDefaultInTheSystemConfiguration(TaxationSettingsProvider::USE_AS_BASE_DESTINATION);
    }

    /**
     * @param string $value
     */
    private function thereIsUseAsBaseByDefaultInTheSystemConfiguration($value): void
    {
        $this->configManager->set('oro_tax.use_as_base_by_default', $value);
        $this->configManager->flush();
    }
}
