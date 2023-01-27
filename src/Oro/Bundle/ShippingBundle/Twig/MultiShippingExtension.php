<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Oro\Bundle\ShippingBundle\Manager\MultiShippingIntegrationManager;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for working with Multi Shipping integration:
 *   - multi_shipping_integration_exists
 */
class MultiShippingExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions(): array
    {
        return [new TwigFunction('multi_shipping_integration_exists', [$this, 'isMultiShippingIntegrationExists'])];
    }

    public function isMultiShippingIntegrationExists(): bool
    {
        return $this->getMultiShippingIntegrationManager()->integrationExists();
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'oro_shipping.manager.multi_shipping_integration' => MultiShippingIntegrationManager::class
        ];
    }

    private function getMultiShippingIntegrationManager(): MultiShippingIntegrationManager
    {
        return $this->container->get('oro_shipping.manager.multi_shipping_integration');
    }
}
