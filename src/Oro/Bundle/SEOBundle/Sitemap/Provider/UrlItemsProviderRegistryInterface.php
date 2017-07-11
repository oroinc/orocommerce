<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Oro\Component\SEO\Provider\UrlItemsProviderInterface;

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
