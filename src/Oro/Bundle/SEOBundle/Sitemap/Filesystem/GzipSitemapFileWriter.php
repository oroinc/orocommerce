<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

class GzipSitemapFileWriter implements SitemapFileWriterInterface
{
    const ARCHIVE_EXTENSION = 'gz';

    /**
     * @var SitemapFileWriterInterface
     */
    private $sitemapFileWriter;

    /**
     * @param SitemapFileWriterInterface $sitemapFileWriter
     */
    public function __construct(SitemapFileWriterInterface $sitemapFileWriter)
    {
        $this->sitemapFileWriter = $sitemapFileWriter;
    }

    /**
     * @param string $siteMapContents
     * @param string $path
     * @return string
     */
    public function saveSitemap($siteMapContents, $path)
    {
        $gzippedContents = gzencode($siteMapContents);
        if (false === $gzippedContents) {
            return $this->sitemapFileWriter->saveSitemap($siteMapContents, $path);
        }

        $path = sprintf('%s.%s', $path, self::ARCHIVE_EXTENSION);

        return $this->sitemapFileWriter->saveSitemap($gzippedContents, $path);
    }
}
