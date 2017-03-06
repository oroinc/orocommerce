<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Component\SEO\Provider\UrlItemsProviderInterface;

class UrlItemsProviderRegistry
{
    /**
     * @var array|UrlItemsProviderInterface[]
     */
    private $providers = [];

    /**
     * @param UrlItemsProviderInterface $provider
     * @param string $name
     */
    public function addProvider(UrlItemsProviderInterface $provider, $name)
    {
        $this->providers[$name] = $provider;
    }

    /**
     * @return array|UrlItemsProviderInterface[]
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
     *
     * @return null|UrlItemsProviderInterface
     */
    public function getProviderByName($name)
    {
        return isset($this->providers[$name]) ? $this->providers[$name] : null;
    }
}
