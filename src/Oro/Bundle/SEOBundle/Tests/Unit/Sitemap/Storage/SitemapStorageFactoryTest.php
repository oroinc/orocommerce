<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Storage;

use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Storage\XmlSitemapIndexStorage;
use Oro\Bundle\SEOBundle\Sitemap\Storage\XmlSitemapUrlsStorage;
use Oro\Bundle\SEOBundle\Tools\Exception\UnsupportedStorageTypeException;

class SitemapStorageFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateUrlsStorage()
    {
        $sitemapStorageFactory = new SitemapStorageFactory();

        $this->assertInstanceOf(XmlSitemapUrlsStorage::class, $sitemapStorageFactory->createUrlsStorage());
    }

    public function testCreateUrlsStorageByType()
    {
        $sitemapStorageFactory = new SitemapStorageFactory();

        $this->assertInstanceOf(XmlSitemapUrlsStorage::class, $sitemapStorageFactory->createUrlsStorage('sitemap'));
    }

    public function testCreateIndexStorage()
    {
        $sitemapStorageFactory = new SitemapStorageFactory();

        $this->assertInstanceOf(XmlSitemapIndexStorage::class, $sitemapStorageFactory->createUrlsStorage('index'));
    }

    public function testCreateIndexStorageUnknownType()
    {
        $sitemapStorageFactory = new SitemapStorageFactory();

        $this->expectException(UnsupportedStorageTypeException::class);
        $this->expectExceptionMessage('Unsupported sitemap storage type test');
        $sitemapStorageFactory->createUrlsStorage('test');
    }
}
