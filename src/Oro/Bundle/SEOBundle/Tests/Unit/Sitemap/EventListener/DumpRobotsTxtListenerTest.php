<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\EventListener;

use Gaufrette\File;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\EventListener\DumpRobotsTxtListener;
use Oro\Bundle\SEOBundle\Sitemap\Exception\LogicException;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Manager\RobotsTxtSitemapManager;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Component\Website\WebsiteInterface;

class DumpRobotsTxtListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SITEMAP_VERSION = '14543456';
    private const SITEMAP_DIR     = 'sitemap';

    /** @var RobotsTxtSitemapManager|\PHPUnit\Framework\MockObject\MockObject */
    private $robotsTxtSitemapManager;

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $canonicalUrlGenerator;

    /** @var SitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $sitemapFilesystemAdapter;

    /** @var DumpRobotsTxtListener */
    private $listener;

    protected function setUp(): void
    {
        $this->robotsTxtSitemapManager = $this->createMock(RobotsTxtSitemapManager::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->sitemapFilesystemAdapter = $this->createMock(SitemapFilesystemAdapter::class);

        $this->listener = new DumpRobotsTxtListener(
            $this->robotsTxtSitemapManager,
            $this->canonicalUrlGenerator,
            $this->sitemapFilesystemAdapter,
            self::SITEMAP_DIR
        );
    }

    private function getWebsite(int $id, bool $isDefault): WebsiteInterface
    {
        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $website->expects($this->any())
            ->method('isDefault')
            ->willReturn($isDefault);

        return $website;
    }

    private function getFile(string $fileName): File
    {
        $file = $this->createMock(File::class);
        $file->expects($this->any())
            ->method('getName')
            ->willReturn($fileName);

        return $file;
    }

    public function testOnSitemapDumpStorageWhenThrowsException()
    {
        $website = $this->getWebsite(1, true);
        $event = new OnSitemapDumpFinishEvent($website, self::SITEMAP_VERSION);
        $this->sitemapFilesystemAdapter->expects($this->once())
            ->method('getSitemapFiles')
            ->with(
                $website,
                SitemapDumper::getFilenamePattern(SitemapStorageFactory::TYPE_SITEMAP_INDEX)
            )
            ->willReturn([]);
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->robotsTxtSitemapManager->expects($this->never())
            ->method('addSitemap');
        $this->robotsTxtSitemapManager->expects($this->never())
            ->method('flush');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot find sitemap index file.');
        $this->listener->onSitemapDumpStorage($event);
    }

    public function testOnSitemapDumpStorage()
    {
        $websiteId = 777;
        $website = $this->getWebsite($websiteId, true);
        $event = new OnSitemapDumpFinishEvent($website, self::SITEMAP_VERSION);
        $filename = 'some_file_name.txt';
        $this->sitemapFilesystemAdapter->expects($this->once())
            ->method('getSitemapFiles')
            ->with(
                $website,
                SitemapDumper::getFilenamePattern(SitemapStorageFactory::TYPE_SITEMAP_INDEX)
            )
            ->willReturn([$this->getFile($filename)]);

        $url = 'http://example.com/sitemap.xml';

        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://example.com/europe/');

        $this->canonicalUrlGenerator->expects($this->once())
            ->method('createUrl')
            ->with(
                'http://example.com',
                sprintf('%s/%s/%s', self::SITEMAP_DIR, $websiteId, $filename)
            )
            ->willReturn($url);

        $this->robotsTxtSitemapManager->expects($this->once())
            ->method('addSitemap')
            ->with($url);
        $this->robotsTxtSitemapManager->expects($this->once())
            ->method('flush');
        $this->listener->onSitemapDumpStorage($event);
    }
}
