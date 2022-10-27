<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The registry of subtotal providers.
 */
class SubtotalProviderRegistry implements ResetInterface
{
    /** @var string[] */
    private $providerNames;

    /** @var ContainerInterface */
    private $providerContainer;

    /** @var SubtotalProviderInterface[]|null */
    private $providers;

    /**
     * @param string[]           $providerNames
     * @param ContainerInterface $providerContainer
     */
    public function __construct(array $providerNames, ContainerInterface $providerContainer)
    {
        $this->providerNames = $providerNames;
        $this->providerContainer = $providerContainer;
    }

    public function reset()
    {
        $this->providers = null;
    }

    /**
     * Gets providers that support the given entity.
     *
     * @param object $entity
     *
     * @return SubtotalProviderInterface[] [provider name => provider, ...]
     */
    public function getSupportedProviders($entity)
    {
        $supportedProviders = [];
        $providers = $this->getProviders();
        foreach ($providers as $name => $provider) {
            if ($provider->isSupported($entity)) {
                $supportedProviders[$name] = $provider;
            }
        }
        return $supportedProviders;
    }

    /**
     * Gets all providers.
     *
     * @return SubtotalProviderInterface[] [provider name => provider, ...]
     */
    public function getProviders()
    {
        if (null === $this->providers) {
            $this->providers = [];
            foreach ($this->providerNames as $name) {
                $this->providers[$name] = $this->providerContainer->get($name);
            }
        }

        return $this->providers;
    }

    /**
     * Gets a provider by its name.
     *
     * @param string $name
     *
     * @return SubtotalProviderInterface|null
     */
    public function getProviderByName(string $name)
    {
        $provider = null;
        if (null === $this->providers) {
            if ($this->providerContainer->has($name)) {
                $provider = $this->providerContainer->get($name);
            }
        } elseif (isset($this->providers[$name])) {
            $provider = $this->providers[$name];
        }

        return $provider;
    }

    /**
     * Checks if a provider with the given name exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasProvider(string $name)
    {
        return $this->providerContainer->has($name);
    }
}
