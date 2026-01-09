<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Component\SEO\Provider\UrlItemsProviderInterface;

/**
 * Registry for managing URL items providers.
 *
 * This class maintains a collection of URL items providers indexed by name and provides methods
 * to retrieve providers by name or as a complete indexed collection. It is typically populated
 * by the dependency injection container through compiler passes.
 */
class UrlItemsProviderRegistry implements UrlItemsProviderRegistryInterface
{
    /**
     * @var array|UrlItemsProviderInterface[]
     */
    private $providers = [];

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    #[\Override]
    public function getProvidersIndexedByNames()
    {
        return $this->providers;
    }

    #[\Override]
    public function getProviderByName($name)
    {
        return isset($this->providers[$name]) ? $this->providers[$name] : null;
    }
}
