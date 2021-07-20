<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Component\Website\WebsiteInterface;

/**
 * Provider that resurns a list of websites for witch the sitemaps should be generated.
 */
interface WebsiteForSitemapProviderInterface
{
    /**
     * @return array WebsiteInterface[]
     */
    public function getAvailableWebsites(): array;
}
