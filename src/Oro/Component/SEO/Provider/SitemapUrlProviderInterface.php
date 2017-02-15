<?php

namespace Oro\Component\SEO\Provider;

use Oro\Component\SEO\Model\DTO\UrlItemInterface;
use Oro\Component\Website\WebsiteInterface;

interface SitemapUrlProviderInterface
{
    /**
     * @param WebsiteInterface $website
     * @return array|UrlItemInterface[]
     */
    public function getUrlItems(WebsiteInterface $website);
}
