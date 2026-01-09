<?php

namespace Oro\Component\SEO\Tools;

use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;
use Oro\Component\Website\WebsiteInterface;

/**
 * Defines the contract for sitemap dumpers that generate and write sitemap files.
 *
 * Implementations generate XML sitemaps for a specific website and version, optionally
 * filtering by type, and write them to the filesystem. This interface supports the
 * creation of search engine-friendly sitemap files.
 */
interface SitemapDumperInterface
{
    /**
     * @param WebsiteInterface $website
     * @param string $version
     * @param string $type
     * @throws SitemapFileWriterException
     */
    public function dump(WebsiteInterface $website, $version, $type = null);
}
