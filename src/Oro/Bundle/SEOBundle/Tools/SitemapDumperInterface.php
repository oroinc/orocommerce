<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Bundle\SEOBundle\Model\WebsiteInterface;

interface SitemapDumperInterface
{
    /**
     * @param WebsiteInterface $website
     * @param string $type
     */
    public function dump(WebsiteInterface $website, $type = null);
}
