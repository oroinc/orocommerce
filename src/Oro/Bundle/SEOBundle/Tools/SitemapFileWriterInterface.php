<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Bundle\SEOBundle\Tools\Exception\SitemapFileWriterException;

interface SitemapFileWriterInterface
{
    /**
     * @param string $sitemapContents
     * @param string $path
     * @throws SitemapFileWriterException
     */
    public function saveSitemap($sitemapContents, $path);
}
