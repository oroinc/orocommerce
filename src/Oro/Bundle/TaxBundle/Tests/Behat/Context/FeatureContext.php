<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Context;

use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext
{
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
        $configManager = $this->getAppContainer()->get('oro_config.global');
        $configManager->set('oro_tax.use_as_base_by_default', $value);
        $configManager->flush();
    }
}
