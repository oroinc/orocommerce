<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;

interface SitemapFileWriterInterface
{
    /**
     * @param string $sitemapContents
     * @param string $path
     * @throws SitemapFileWriterException
     */
    public function saveSitemap($sitemapContents, $path);
}
