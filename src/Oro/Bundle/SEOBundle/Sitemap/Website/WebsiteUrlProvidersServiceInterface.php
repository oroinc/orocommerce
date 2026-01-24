<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Website;

use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;

/**
 * Defines the contract for website-specific URL items provider services.
 *
 * This interface specifies the method for retrieving URL items providers that are appropriate for a specific website.
 * Implementations can apply website-specific logic to determine which providers should be used.
 */
interface WebsiteUrlProvidersServiceInterface
{
    /**
     * @param WebsiteInterface $website
     *
     * @return UrlItemsProviderInterface[]
     */
    public function getWebsiteProvidersIndexedByNames(WebsiteInterface $website);
}
