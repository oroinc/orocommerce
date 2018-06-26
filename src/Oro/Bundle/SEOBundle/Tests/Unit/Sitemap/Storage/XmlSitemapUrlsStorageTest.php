<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Storage;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Storage\XmlSitemapUrlsStorage;

class XmlSitemapUrlsStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testStorageWhenNoUrlItemsWereAdded()
    {
        $sitemapStorage = new XmlSitemapUrlsStorage();

        $expectedXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>
XML;

        $this->assertXmlStringEqualsXmlString($expectedXml, $sitemapStorage->getContents());
    }

    public function testStorageWhenUrlItemsWereAdded()
    {
        $sitemapStorage = new XmlSitemapUrlsStorage();
        $sitemapStorage->addUrlItem(new UrlItem('http://somelocation.com/'));
        $otherDateTime = new \DateTime('2017-01-01 17:33');
        $sitemapStorage->addUrlItem(new UrlItem('http://otherlocation.com/', $otherDateTime));
        $anotherDateTime = new \DateTime('2017-05-03 15:45');
        $sitemapStorage->addUrlItem(new UrlItem('http://anotherlocation.com/', $anotherDateTime, 'daily', '0.5'));

        $otherDateTimeString = $otherDateTime->format(\DateTime::W3C);
        $anotherDateTimeString = $anotherDateTime->format(\DateTime::W3C);
        $expectedXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
XML;
        $expectedXml .= '<url><loc>http://somelocation.com/</loc></url>';
        $expectedXml .= "<url><loc>http://otherlocation.com/</loc><lastmod>$otherDateTimeString</lastmod></url>";
        $expectedXml .= '<url><loc>http://anotherlocation.com/</loc><changefreq>daily</changefreq>';
        $expectedXml .= "<priority>0.5</priority><lastmod>$anotherDateTimeString</lastmod></url>";
        $expectedXml .= '</urlset>';

        $this->assertXmlStringEqualsXmlString($expectedXml, $sitemapStorage->getContents());
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

        $this->assertXmlStringEqualsXmlString($expectedXml, $sitemapStorage->getContents());
    }
}
