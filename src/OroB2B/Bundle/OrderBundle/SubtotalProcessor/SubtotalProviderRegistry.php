<?php

namespace OroB2B\Bundle\OrderBundle\SubtotalProcessor;

class SubtotalProviderRegistry
{
    /**
     * @var SubtotalProviderInterface[]
     */
    protected $providers = [];

    /**
     * @param SubtotalProviderInterface $provider
     */
    public function addProvider(SubtotalProviderInterface $provider)
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * @return SubtotalProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
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
     * @param string $name
     *
     * @return bool
     */
    public function hasProvider($name)
    {
        return array_key_exists($name, $this->providers);
    }
}
