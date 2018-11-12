<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;

class UrlItemTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $location = 'http://example.com/';
        $changeFrequency = 'daily';
        $priority = 0.5;
        $lastModification = new \DateTime();
        $urlItem = new UrlItem($location, $lastModification, $changeFrequency, $priority);

        $this->assertSame($location, $urlItem->getLocation());
        $this->assertSame($changeFrequency, $urlItem->getChangeFrequency());
        $this->assertSame($priority, $urlItem->getPriority());
        $this->assertSame($lastModification->format(\DateTime::W3C), $urlItem->getLastModification());
    }

    public function testCreateDefaultValues()
    {
        $location = 'http://example.com/';
        $urlItem = new UrlItem($location);

        $this->assertSame($location, $urlItem->getLocation());
        $this->assertNull($urlItem->getChangeFrequency());
        $this->assertNull($urlItem->getPriority());
        $this->assertNull($urlItem->getLastModification());
    }
}
