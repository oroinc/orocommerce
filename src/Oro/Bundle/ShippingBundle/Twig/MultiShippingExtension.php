<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\ShippingBundle\Manager\MultiShippingIntegrationManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for working with multi shipping integration:
 *   - multi_shipping_integration_exists
 */
class MultiShippingExtension extends AbstractExtension
{
    private MultiShippingIntegrationManager $multiShippingIntegrationManager;

    public function __construct(MultiShippingIntegrationManager $multiShippingIntegrationManager)
    {
        $this->multiShippingIntegrationManager = $multiShippingIntegrationManager;
    }

    public function getFunctions()
    {
        return [new TwigFunction('multi_shipping_integration_exists', [$this, 'isMultiShippingIntegrationExists'])];
    }

    public function isMultiShippingIntegrationExists(): bool
    {
        return $this->multiShippingIntegrationManager->integrationExists();
    }
}
