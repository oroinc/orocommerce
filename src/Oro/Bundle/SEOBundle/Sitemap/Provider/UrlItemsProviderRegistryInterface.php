<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Component\SEO\Provider\UrlItemsProviderInterface;

/**
 * Defines the contract for accessing registered URL items providers.
 *
 * This interface provides methods to retrieve URL items providers that have been registered with the system.
 * Implementations maintain a registry of providers and allow retrieval by name or as a complete indexed collection.
 */
interface UrlItemsProviderRegistryInterface
{
    /**
     * @return array|UrlItemsProviderInterface[]
     */
    public function getProvidersIndexedByNames();

    /**
     * @param string $name
     *
     * @return null|UrlItemsProviderInterface
     */
    public function getProviderByName($name);
}
