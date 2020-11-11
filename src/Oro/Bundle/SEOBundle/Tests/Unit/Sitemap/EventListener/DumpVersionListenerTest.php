<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\EventListener;

use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\EventListener\DumpVersionListener;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Component\Website\WebsiteInterface;

class DumpVersionListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $filesystemAdapter;

    /** @var DumpVersionListener */
    private $listener;

    protected function setUp(): void
    {
        $this->filesystemAdapter = $this->createMock(SitemapFilesystemAdapter::class);

        $this->listener = new DumpVersionListener($this->filesystemAdapter);
    }

    public function testOnSitemapDumpStorage()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $version = 'some_version';

        $this->filesystemAdapter->expects($this->once())
            ->method('dumpVersion')
            ->with($this->identicalTo($website), $version);

        $this->listener->onSitemapDumpStorage(new OnSitemapDumpFinishEvent($website, $version));
    }
}
