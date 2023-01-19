<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Provides shipping methods for a specific integration channel type.
 */
class ChannelShippingMethodProvider implements ShippingMethodProviderInterface
{
    private string $channelType;
    private IntegrationShippingMethodFactoryInterface $methodFactory;
    private ChannelLoaderInterface $channelLoader;
    private ?array $shippingMethods = null;

    public function __construct(
        string $channelType,
        IntegrationShippingMethodFactoryInterface $methodFactory,
        ChannelLoaderInterface $channelLoader,
    ) {
        $this->channelType = $channelType;
        $this->methodFactory = $methodFactory;
        $this->channelLoader = $channelLoader;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethods(): array
    {
        if (null === $this->shippingMethods) {
            $this->shippingMethods = $this->loadShippingMethods();
        }

        return $this->shippingMethods;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethod(string $name): ?ShippingMethodInterface
    {
        $shippingMethods = $this->getShippingMethods();

        return $shippingMethods[$name] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function hasShippingMethod(string $name): bool
    {
        $shippingMethods = $this->getShippingMethods();

        return isset($shippingMethods[$name]);
    }

    private function loadShippingMethods(): array
    {
        $shippingMethods = [];
        $channels = $this->channelLoader->loadChannels($this->channelType, true);
        foreach ($channels as $channel) {
            $method = $this->methodFactory->create($channel);
            $shippingMethods[$method->getIdentifier()] = $method;
        }

        return $shippingMethods;
    }
}
