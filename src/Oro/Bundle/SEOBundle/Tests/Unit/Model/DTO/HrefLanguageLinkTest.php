<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\SEOBundle\Model\DTO\HrefLanguageLink;
use Oro\Component\SEO\Model\DTO\HrefLanguageLinkInterface;

class UrlItemLinkTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $href = 'http://example.com/';
        $hrefLanguage = 'en';
        $rel = 'alternate';
        $urlItemLink = new HrefLanguageLink($href, $hrefLanguage, $rel);

        $this->assertSame($href, $urlItemLink->getHref());
        $this->assertSame($hrefLanguage, $urlItemLink->getHrefLanguage());
        $this->assertSame($rel, $urlItemLink->getRel());
    }

    public function testCreateDefaultValues()
    {
        $href = 'http://example.com/';
        $urlItemLink = new HrefLanguageLink($href);

        $this->assertSame($href, $urlItemLink->getHref());
        $this->assertSame(HrefLanguageLinkInterface::HREF_LANGUAGE_DEFAULT, $urlItemLink->getHrefLanguage());
        $this->assertSame(HrefLanguageLinkInterface::REL_ALTERNATE, $urlItemLink->getRel());
    }
}
