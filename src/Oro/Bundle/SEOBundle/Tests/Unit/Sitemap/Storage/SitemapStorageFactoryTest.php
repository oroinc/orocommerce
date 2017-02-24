<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Storage;

use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;

class SitemapStorageFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateUrlsStorage()
    {
        $sitemapStorageFactory = new SitemapStorageFactory();

        $this->assertInstanceOf(SitemapStorageInterface::class, $sitemapStorageFactory->createUrlsStorage());
    }
}
