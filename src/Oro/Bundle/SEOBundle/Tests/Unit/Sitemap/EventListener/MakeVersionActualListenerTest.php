<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\EventListener;

use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\EventListener\MakeVersionActualListener;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Component\Website\WebsiteInterface;

class MakeVersionActualListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filesystemAdapter;

    /**
     * @var MakeVersionActualListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->filesystemAdapter = $this->getMockBuilder(SitemapFilesystemAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new MakeVersionActualListener($this->filesystemAdapter);
    }

    public function testOnSitemapDumpStorage()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 'some_version';

        $this->filesystemAdapter->expects($this->once())
            ->method('makeNewerVersionActual')
            ->with($website, $version);
        $event = new OnSitemapDumpFinishEvent($website, $version);
        $this->listener->onSitemapDumpStorage($event);
    }
}
