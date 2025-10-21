<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\EventListener;

use Oro\Bundle\SEOBundle\Provider\WebsiteForSitemapProviderInterface;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\EventListener\DumpVersionListener;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Component\Website\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DumpVersionListenerTest extends TestCase
{
    private SitemapFilesystemAdapter|MockObject $filesystemAdapter;

    private WebsiteForSitemapProviderInterface|MockObject $websiteForSitemapProvider;

    private DumpVersionListener $listener;

    protected function setUp(): void
    {
        $this->filesystemAdapter = $this->createMock(SitemapFilesystemAdapter::class);
        $this->websiteForSitemapProvider = $this->createMock(WebsiteForSitemapProviderInterface::class);

        $this->listener = new DumpVersionListener(
            $this->filesystemAdapter
        );
        $this->listener->setWebsiteForSitemapProvider($this->websiteForSitemapProvider);
    }

    public function testOnSitemapDumpStorageWithNotAvailableWebsites()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $version = 'some_version';

        $this->filesystemAdapter->expects($this->never())
            ->method('dumpVersion')
            ->with($this->identicalTo($website), $version);

        $this->listener->onSitemapDumpStorage(new OnSitemapDumpFinishEvent($website, $version));
    }

    public function testOnSitemapDumpStorage()
    {
        $website = $this->createMock(WebsiteInterface::class);
        $version = 'some_version';

        $this->websiteForSitemapProvider->expects($this->once())
            ->method('getAvailableWebsites')
            ->willReturn([$website]);

        $this->filesystemAdapter->expects($this->once())
            ->method('dumpVersion')
            ->with($this->identicalTo($website), $version);

        $this->listener->onSitemapDumpStorage(new OnSitemapDumpFinishEvent($website, $version));
    }
}
