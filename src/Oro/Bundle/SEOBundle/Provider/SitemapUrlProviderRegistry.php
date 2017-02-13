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
     */
    public function registerProvider(SitemapUrlProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function isSupported($entityClass)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isSupported($entityClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $entityClass
     * @return null|SitemapUrlProviderInterface
     */
    public function getProviderByClass($entityClass)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isSupported($entityClass)) {
                return $provider;
            }
        }

        return null;
    }
}
