<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Website;

use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Component\Website\WebsiteInterface;

/**
 * Provides website-specific URL index providers.
 *
 * This service returns URL index providers for a website. It is used when generating sitemap index files
 * that reference individual sitemap files. Unlike the regular {@see WebsiteUrlProvidersService},
 * this service does not apply guest access restrictions.
 */
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

    #[\Override]
    public function getWebsiteProvidersIndexedByNames(WebsiteInterface $website)
    {
        return $this->urlIndexProviderRegistry->getProvidersIndexedByNames();
    }
}
