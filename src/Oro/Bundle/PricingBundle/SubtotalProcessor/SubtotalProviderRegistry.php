<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

class SubtotalProviderRegistry
{
    /**
     * @var SubtotalProviderInterface[]
     */
    protected $providers = [];

    /**
     * Add provider to registry
     *
     * @param SubtotalProviderInterface $provider
     */
    public function addProvider(SubtotalProviderInterface $provider)
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Get supported provider list
     *
     * @param $entity
     *
     * @return SubtotalProviderInterface[]
     */
    public function getSupportedProviders($entity)
    {
        $providers = [];
        foreach ($this->providers as $provider) {
            if ($provider->isSupported($entity)) {
                $providers[] = $provider;
            }
        }
        return $providers;
    }


    /**
     * Get all providers
     *
     * @return SubtotalProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Get provider by name
     *
     * @param string $name
     *
     * @return null|SubtotalProviderInterface
     */
    public function getProviderByName($name)
    {
        if ($this->hasProvider($name)) {
            return $this->providers[$name];
        }

        return null;
    }

    /**
     * Check available provider by name
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasProvider($name)
    {
        return array_key_exists($name, $this->providers);
    }
}
