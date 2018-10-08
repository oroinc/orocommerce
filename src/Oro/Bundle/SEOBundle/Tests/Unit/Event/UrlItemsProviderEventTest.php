<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Event;

use Oro\Bundle\SEOBundle\Event\UrlItemsProviderEvent;
use Oro\Component\Website\WebsiteInterface;

class UrlItemsProviderEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEventWithWebsite()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;
        $event = new UrlItemsProviderEvent($version, $website);

        $this->assertEquals($version, $event->getVersion());
        $this->assertEquals($website, $event->getWebsite());
    }

    public function testEventWithoutWebsite()
    {
        $version = 1;
        $event = new UrlItemsProviderEvent($version);
        $this->assertNull($event->getWebsite());
    }
}
