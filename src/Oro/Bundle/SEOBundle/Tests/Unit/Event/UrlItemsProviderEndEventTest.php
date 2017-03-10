<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Event;

use Oro\Bundle\SEOBundle\Event\UrlItemsProviderEndEvent;
use Oro\Component\Website\WebsiteInterface;

class UrlItemsProviderEndEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlItemsProviderEndEvent
     */
    protected $urlItemsProviderEndEvent;

    public function testEventWithWebsite()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;
        $event = new UrlItemsProviderEndEvent($version, $website);

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertEquals($version, $event->getVersion());
        $this->assertEquals($website, $event->getWebsite());
    }

    public function testEventWithoutWebsite()
    {
        $version = 1;
        $event = new UrlItemsProviderEndEvent($version);

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertNull($event->getWebsite());
    }
}
