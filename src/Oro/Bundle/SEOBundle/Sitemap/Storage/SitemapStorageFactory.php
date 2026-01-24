<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Storage;

use Oro\Bundle\SEOBundle\Sitemap\Exception\UnsupportedStorageTypeException;

/**
 * Factory for creating sitemap storage instances.
 *
 * This factory creates appropriate sitemap storage instances based on the requested type.
 * It supports creating storage for regular sitemaps and sitemap index files, throwing an exception
 * for unsupported storage types.
 */
class SitemapStorageFactory
{
    const TYPE_SITEMAP = 'sitemap';
    const TYPE_SITEMAP_INDEX = 'index';

    /**
     * @param string $type
     * @return SitemapStorageInterface
     * @throws UnsupportedStorageTypeException
     */
    public function createUrlsStorage($type = self::TYPE_SITEMAP)
    {
        switch ($type) {
            case self::TYPE_SITEMAP:
                return new XmlSitemapUrlsStorage();
            case self::TYPE_SITEMAP_INDEX:
                return new XmlSitemapIndexStorage();
            default:
                throw new UnsupportedStorageTypeException($type);
        }
    }
}
