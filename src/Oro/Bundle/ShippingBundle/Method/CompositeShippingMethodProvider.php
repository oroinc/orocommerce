<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * Uses all registered shipping method providers to get shipping methods.
 */
class CompositeShippingMethodProvider implements ShippingMethodProviderInterface
{
    /** @var iterable<ShippingMethodProviderInterface> */
    private iterable $providers;

    /**
     * @param iterable<ShippingMethodProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethods(): array
    {
        $items = [];
        foreach ($this->providers as $provider) {
            $items[] = $provider->getShippingMethods();
        }
        if ($items) {
            $items = array_merge(...$items);
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethod(string $name): ?ShippingMethodInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasShippingMethod($name)) {
                return $provider->getShippingMethod($name);
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function hasShippingMethod(string $name): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasShippingMethod($name)) {
                return true;
            }
        }

        return false;
    }
}
