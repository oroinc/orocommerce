<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools;

use Oro\Bundle\SEOBundle\Tools\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Tools\SitemapUrlsStorageInterface;

class SitemapStorageFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateUrlsStorage()
    {
        $sitemapStorageFactory = new SitemapStorageFactory();

        $this->assertInstanceOf(SitemapUrlsStorageInterface::class, $sitemapStorageFactory->createUrlsStorage());
    }
}
