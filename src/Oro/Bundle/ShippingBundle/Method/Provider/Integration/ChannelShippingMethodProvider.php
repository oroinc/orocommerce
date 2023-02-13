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
    private IntegrationShippingMethodFactoryInterface $shippingMethodFactory;
    private ShippingMethodLoader $shippingMethodLoader;

    public function __construct(
        string $channelType,
        IntegrationShippingMethodFactoryInterface $shippingMethodFactory,
        ShippingMethodLoader $shippingMethodLoader,
    ) {
        $this->channelType = $channelType;
        $this->shippingMethodFactory = $shippingMethodFactory;
        $this->shippingMethodLoader = $shippingMethodLoader;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethods(): array
    {
        return $this->shippingMethodLoader->loadShippingMethods($this->channelType, $this->shippingMethodFactory);
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
}
