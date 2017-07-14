<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Website;

use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;

interface WebsiteUrlProvidersServiceInterface
{
    /**
     * @param WebsiteInterface $website
     *
     * @return UrlItemsProviderInterface[]
     */
    public function getWebsiteProvidersIndexedByNames(WebsiteInterface $website);
}
