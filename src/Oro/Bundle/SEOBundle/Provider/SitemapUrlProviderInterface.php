<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Component\Website\WebsiteInterface;

interface SitemapUrlProviderInterface
{
    /**
     * @param WebsiteInterface $website
     * @return array|UrlItem[]
     */
    public function getUrlItems(WebsiteInterface $website);
}
