<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Bundle\SEOBundle\Model\WebsiteInterface;

interface SitemapUrlProviderInterface
{
    /**
     * @param WebsiteInterface $website
     * @return array
     */
    public function getUrls(WebsiteInterface $website);
}
