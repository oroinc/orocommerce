<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Event;

use Oro\Bundle\SEOBundle\Event\UrlItemsProviderStartEvent;
use Oro\Component\Website\WebsiteInterface;

class UrlItemsProviderStartEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlItemsProviderStartEvent
     */
    protected $urlItemsProviderStartEvent;

    public function testEventWithWebsite()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $event = new UrlItemsProviderStartEvent($version, $website);

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertEquals($version, $event->getVersion());
        $this->assertEquals($website, $event->getWebsite());
    }

    public function testEventWithoutWebsite()
    {
        $version = 2;
        $event = new UrlItemsProviderStartEvent($version);

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertEquals($version, $event->getVersion());
        $this->assertNull($event->getWebsite());
    }
}
