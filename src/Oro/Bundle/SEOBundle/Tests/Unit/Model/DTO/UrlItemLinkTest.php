<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItemLink;

class UrlItemLinkTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $rel = 'alternate';
        $hrefLanguage = 'en';
        $href = 'http://example.com/';
        $urlItemLink = new UrlItemLink($rel, $hrefLanguage, $href);

        $this->assertSame($rel, $urlItemLink->getRel());
        $this->assertSame($hrefLanguage, $urlItemLink->getHrefLanguage());
        $this->assertSame($href, $urlItemLink->getHref());
    }

    public function testCreateDefaultValues()
    {
        $urlItemLink = new UrlItemLink();

        $this->assertNull($urlItemLink->getRel());
        $this->assertNull($urlItemLink->getHrefLanguage());
        $this->assertNull($urlItemLink->getHref());
    }
}
