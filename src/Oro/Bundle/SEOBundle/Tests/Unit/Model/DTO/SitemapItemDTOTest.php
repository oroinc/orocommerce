<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\SEOBundle\Model\DTO\SitemapItemDTO;

class SitemapItemDTOTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $loc = 'http://example.com/';
        $changefreq = 'daily';
        $priority = 0.5;
        $lastmod = new \DateTime();
        $dto = new SitemapItemDTO($loc, $changefreq, $priority, $lastmod);

        $this->assertSame($loc, $dto->getLoc());
        $this->assertSame($changefreq, $dto->getChangefreq());
        $this->assertSame($priority, $dto->getPriority());
        $this->assertSame($lastmod->format(\DateTime::W3C), $dto->getLastmod());
    }

    public function testCreateDefaultValues()
    {
        $loc = 'http://example.com/';
        $dto = new SitemapItemDTO($loc);

        $this->assertSame($loc, $dto->getLoc());
        $this->assertNull($dto->getChangefreq());
        $this->assertNull($dto->getPriority());
        $this->assertNull($dto->getLastmod());
    }
}
