<?php

namespace Oro\Bundle\SEOBundle\Tools;

class SitemapStorageFactory
{
    /**
     * @return SitemapUrlsStorageInterface
     */
    public function createUrlsStorage()
    {
        return new XmlSitemapUrlsStorage();
    }
}
