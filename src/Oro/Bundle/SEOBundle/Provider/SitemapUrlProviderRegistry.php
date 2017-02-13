<?php

namespace Oro\Bundle\SEOBundle\Provider;

class SitemapUrlProviderRegistry
{
    /**
     * @var array|SitemapUrlProviderInterface[]
     */
    private $providers = [];

    /**
     * @param SitemapUrlProviderInterface $provider
     * @param string $name
     */
    public function registerProvider(SitemapUrlProviderInterface $provider, $name)
    {
        $this->providers[$name] = $provider;
    }

    /**
     * @return array|SitemapUrlProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * @return array
     */
    public function getProviderNames()
    {
        return array_keys($this->providers);
    }

    /**
     * @param string $name
     * @return null|SitemapUrlProviderInterface
     */
    public function getProviderByName($name)
    {
        return isset($this->providers[$name]) ? $this->providers[$name] : null;
    }
}
