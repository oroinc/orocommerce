<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Storage;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Storage\XmlSitemapIndexStorage;

class XmlSitemapIndexStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testCorrectXmlTagsAdded()
    {
        $sitemapStorage = new XmlSitemapIndexStorage();
        $sitemapStorage->addUrlItem(new UrlItem('http://test.com/sitemap-type-1.xml'));
        $dateTimeOne = new \DateTime('2017-01-01 17:33');
        $sitemapStorage->addUrlItem(new UrlItem('http://test.com/sitemap-type-2.xml', $dateTimeOne));
        $dateTimeTwo = new \DateTime('2017-05-03 15:45');
        $sitemapStorage->addUrlItem(new UrlItem('http://test.com/sitemap-type-3.xml', $dateTimeTwo, 'daily', '0.5'));

        $dateTimeOneW3C = $dateTimeOne->format(\DateTime::W3C);
        $dateTimeTwoW3C = $dateTimeTwo->format(\DateTime::W3C);
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
XML;
        $xml .= '<sitemap><loc>http://test.com/sitemap-type-1.xml</loc></sitemap>';
        $xml .= "<sitemap><loc>http://test.com/sitemap-type-2.xml</loc><lastmod>$dateTimeOneW3C</lastmod></sitemap>";
        $xml .= "<sitemap><loc>http://test.com/sitemap-type-3.xml</loc><lastmod>$dateTimeTwoW3C</lastmod>";
        $xml .= '</sitemap>';
        $xml .= '</sitemapindex>';

        $this->assertXmlStringEqualsXmlString($xml, $sitemapStorage->getContents());
    }
}
