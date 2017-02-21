<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Tools\XmlSitemapUrlsStorage;

class XmlSitemapUrlsStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testStorageWhenNoUrlItemsWereAdded()
    {
        $sitemapStorage = new XmlSitemapUrlsStorage();

        $expectedXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>
XML;

        $this->assertEquals($expectedXml, $sitemapStorage->getContents());
    }

    public function testStorageWhenUrlItemsWereAdded()
    {
        $sitemapStorage = new XmlSitemapUrlsStorage();
        $sitemapStorage->addUrlItem(new UrlItem('http://somelocation.com/'));
        $sitemapStorage->addUrlItem(new UrlItem('http://otherlocation.com/', new \DateTime('2017-01-01 17:33')));
        $sitemapStorage->addUrlItem(
            new UrlItem('http://anotherlocation.com/', new \DateTime('2017-05-03 15:45'), 'daily', '0.5')
        );

        $expectedXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
XML;
        $expectedXml .= '<url><loc>http://somelocation.com/</loc></url>';
        $expectedXml .= '<url><loc>http://otherlocation.com/</loc><lastmod>2017-01-01T17:33:00+02:00</lastmod></url>';
        $expectedXml .= '<url><loc>http://anotherlocation.com/</loc><changefreq>daily</changefreq>';
        $expectedXml .= '<priority>0.5</priority><lastmod>2017-05-03T15:45:00+03:00</lastmod></url>';
        $expectedXml .= '</urlset>';

        $this->assertEquals($expectedXml, $sitemapStorage->getContents());
    }

    public function testStorageWhenCountLimitReached()
    {
        $urlItem = new UrlItem('http://somelocation.com/');
        $sitemapStorage = new XmlSitemapUrlsStorage(2);

        $this->assertTrue($sitemapStorage->addUrlItem($urlItem));
        $this->assertTrue($sitemapStorage->addUrlItem($urlItem));

        $this->assertFalse($sitemapStorage->addUrlItem($urlItem));
    }

    public function testStorageWhenSizeLimitReached()
    {
        $urlItem = new UrlItem('http://somelocation.com/');
        $sitemapStorage = new XmlSitemapUrlsStorage(10, 200);

        $this->assertTrue($sitemapStorage->addUrlItem($urlItem));
        $this->assertTrue($sitemapStorage->addUrlItem($urlItem));
        $this->assertFalse($sitemapStorage->addUrlItem($urlItem));

        $expectedXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
XML;

        $expectedXml .= '<url><loc>http://somelocation.com/</loc></url>';
        $expectedXml .= '<url><loc>http://somelocation.com/</loc></url>';
        $expectedXml .= '</urlset>';

        $this->assertEquals(200, strlen($sitemapStorage->getContents()));
        $this->assertEquals($expectedXml, $sitemapStorage->getContents());
    }
}
