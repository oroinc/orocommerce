<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given Tax base by default is set to "shipping_origin"
     */
    public function taxBaseIsDefaultShipping()
    {
        $this->thereIsUseAsBaseByDefaultInTheSystemConfiguration('shipping_origin');
    }

    /**
     * @Given Tax base by default is set to "destination"
     */
    public function taxBaseIsDefaultDestination()
    {
        $this->thereIsUseAsBaseByDefaultInTheSystemConfiguration('destination');
    }

    /**
     * @param string $value
     */
    private function thereIsUseAsBaseByDefaultInTheSystemConfiguration($value)
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_tax.use_as_base_by_default', $value);
        $configManager->flush();
    }
}
