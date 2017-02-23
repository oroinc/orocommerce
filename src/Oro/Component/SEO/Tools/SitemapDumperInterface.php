<?php

namespace Oro\Component\SEO\Tools;

use Oro\Component\Website\WebsiteInterface;

interface SitemapDumperInterface
{
    /**
     * @param WebsiteInterface $website
     * @param string $version
     * @param string $type
     */
    public function dump(WebsiteInterface $website, $version, $type = null);
}
