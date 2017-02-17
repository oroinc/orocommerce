<?php

namespace Oro\Component\SEO\Tools;

use Oro\Component\Website\WebsiteInterface;

interface SitemapDumperInterface
{
    const SITEMAP_LOCATION = '../web/sitemap';
    const SITEMAP_FILENAME_TEMPLATE = '%s/sitemap-%s-%s.xml';

    /**
     * @param WebsiteInterface $website
     * @param string $type
     */
    public function dump(WebsiteInterface $website, $type = null);
}
