<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Event;

use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Component\Website\WebsiteInterface;

class OnSitemapDumpFinishEventTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialization()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 'some_version';
        $event = new OnSitemapDumpFinishEvent($website, $version);

        $this->assertSame($website, $event->getWebsite());
        $this->assertSame($version, $event->getVersion());
    }
}
