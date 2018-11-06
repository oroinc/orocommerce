<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given /^Default tax is set to "(?P<value>[\w\s\_]+)"$/
     * @param string $value
     */
    public function taxBaseIsDefaultDestination(string $value): void
    {
        $this->thereIsUseAsBaseByDefaultInTheSystemConfiguration($value);
    }

    /**
     * @param string $value
     */
    private function thereIsUseAsBaseByDefaultInTheSystemConfiguration($value): void
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_tax.use_as_base_by_default', $value);
        $configManager->flush();
    }
}
