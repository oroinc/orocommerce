<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * The registry of shipping method providers.
 */
class CompositeShippingMethodProvider implements ShippingMethodProviderInterface
{
    /** @var iterable|ShippingMethodProviderInterface[] */
    private $providers;

    /**
     * @param iterable|ShippingMethodProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethod($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasShippingMethod($identifier)) {
                return $provider->getShippingMethod($identifier);
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethods()
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
    public function hasShippingMethod($name)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasShippingMethod($name)) {
                return true;
            }
        }

        return false;
    }
}
