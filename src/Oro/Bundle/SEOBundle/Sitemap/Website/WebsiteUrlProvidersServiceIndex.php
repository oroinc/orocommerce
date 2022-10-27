<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Website;

use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Component\Website\WebsiteInterface;

class WebsiteUrlProvidersServiceIndex implements WebsiteUrlProvidersServiceInterface
{
    /**
     * @var UrlItemsProviderRegistryInterface
     */
    private $urlIndexProviderRegistry;

    public function __construct(
        UrlItemsProviderRegistryInterface $urlIndexProviderRegistry
    ) {
        $this->urlIndexProviderRegistry = $urlIndexProviderRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebsiteProvidersIndexedByNames(WebsiteInterface $website)
    {
        return $this->urlIndexProviderRegistry->getProvidersIndexedByNames();
    }
}
