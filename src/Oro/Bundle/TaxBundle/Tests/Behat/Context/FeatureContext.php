<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Context;

use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext
{
    /**
     * @Given Base tax value is set to "Origin"
     */
    public function taxBaseIsDefaultOrigin(): void
    {
        $this->thereIsUseAsBaseByDefaultInTheSystemConfiguration(TaxationSettingsProvider::USE_AS_BASE_ORIGIN);
    }

    /**
     * @Given Base tax value is set to "Destination"
     */
    public function taxBaseIsDefaultDestination(): void
    {
        $this->thereIsUseAsBaseByDefaultInTheSystemConfiguration(TaxationSettingsProvider::USE_AS_BASE_DESTINATION);
    }

    private function thereIsUseAsBaseByDefaultInTheSystemConfiguration(string  $value): void
    {
        $configManager = $this->getAppContainer()->get('oro_config.global');
        $configManager->set('oro_tax.use_as_base_by_default', $value);
        $configManager->flush();
    }
}
