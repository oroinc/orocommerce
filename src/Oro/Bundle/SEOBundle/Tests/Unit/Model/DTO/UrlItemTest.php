<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Model\DTO;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItemLink;

class UrlItemTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $location = 'http://example.com/';
        $changeFrequency = 'daily';
        $priority = 0.5;
        $lastModification = new \DateTime();
        $links = new ArrayCollection([new UrlItemLink()]);
        $urlItem = new UrlItem($location, $changeFrequency, $priority, $lastModification, $links);

        $this->assertSame($location, $urlItem->getLocation());
        $this->assertSame($changeFrequency, $urlItem->getChangeFrequency());
        $this->assertSame($priority, $urlItem->getPriority());
        $this->assertSame($lastModification->format(\DateTime::W3C), $urlItem->getLastModification());
        $this->assertSame($links, $urlItem->getLinks());
    }

    public function testCreateDefaultValues()
    {
        $location = 'http://example.com/';
        $urlItem = new UrlItem($location);

        $this->assertSame($location, $urlItem->getLocation());
        $this->assertNull($urlItem->getChangeFrequency());
        $this->assertNull($urlItem->getPriority());
        $this->assertNull($urlItem->getLastModification());
        $this->assertInstanceOf(ArrayCollection::class, $urlItem->getLinks());
        $this->assertEmpty($urlItem->getLinks()->toArray());
    }
}
