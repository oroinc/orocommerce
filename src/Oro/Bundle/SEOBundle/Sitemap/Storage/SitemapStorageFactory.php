<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Storage;

use Oro\Bundle\SEOBundle\Sitemap\Exception\UnsupportedStorageTypeException;

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
