<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;

/**
 * Represents a way to write sitemap related data to files.
 */
interface SitemapFileWriterInterface
{
    /**
     * @param string $content
     * @param string $path
     *
     * @return string the path to the created file
     *
     * @throws SitemapFileWriterException
     */
    public function saveSitemap(string $content, string $path): string;
}
