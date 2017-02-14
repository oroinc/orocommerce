<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Component\Website\WebsiteInterface;

interface SitemapUrlProviderInterface
{
    /**
     * @param WebsiteInterface $website
     * @return array
     */
    public function getUrls(WebsiteInterface $website);
}
