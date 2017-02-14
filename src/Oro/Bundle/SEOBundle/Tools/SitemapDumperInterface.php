<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Component\Website\WebsiteInterface;

interface SitemapDumperInterface
{
    /**
     * @param WebsiteInterface $website
     * @param string $type
     */
    public function dump(WebsiteInterface $website, $type = null);
}
