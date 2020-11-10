<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

/**
 * Compresses sitemap related data using gzip before it is written to files.
 */
class GzipSitemapFileWriter implements SitemapFileWriterInterface
{
    /** @var SitemapFileWriterInterface */
    private $sitemapFileWriter;

    /**
     * @param SitemapFileWriterInterface $sitemapFileWriter
     */
    public function __construct(SitemapFileWriterInterface $sitemapFileWriter)
    {
        $this->sitemapFileWriter = $sitemapFileWriter;
    }

    /**
     * {@inheritDoc}
     */
    public function saveSitemap(string $content, string $path): string
    {
        $gzippedContent = gzencode($content);
        if (false === $gzippedContent) {
            return $this->sitemapFileWriter->saveSitemap($content, $path);
        }

        return $this->sitemapFileWriter->saveSitemap($gzippedContent, $path . '.gz');
    }
}
