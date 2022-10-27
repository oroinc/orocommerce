<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Gaufrette\File;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Provider\SitemapFilesProvider;
use Oro\Component\Website\WebsiteInterface;

class SitemapFilesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $filesystemAdapter;

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $canonicalUrlGenerator;

    /** @var string */
    private $webPath;

    /** @var SitemapFilesProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->filesystemAdapter = $this->createMock(SitemapFilesystemAdapter::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->webPath = '/sitemaps';

        $this->provider = new SitemapFilesProvider(
            $this->filesystemAdapter,
            $this->canonicalUrlGenerator,
            $this->webPath
        );
    }

    private function getWebsite(int $id): WebsiteInterface
    {
        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $website;
    }

    private function getFile(string $fileName): File
    {
        $file = $this->createMock(File::class);
        $file->expects($this->any())
            ->method('getName')
            ->willReturn($fileName);
        $file->expects($this->any())
            ->method('getMtime')
            ->willReturn(time());

        return $file;
    }

    public function testGetUrlItemsNoFiles()
    {
        $website = $this->getWebsite(123);

        $this->filesystemAdapter->expects($this->once())
            ->method('getSitemapFiles')
            ->with($this->identicalTo($website), $this->isNull(), 'sitemap-index-*.xml*')
            ->willReturn([]);

        $this->canonicalUrlGenerator->expects($this->never())
            ->method($this->anything());

        $this->assertEquals([], iterator_to_array($this->provider->getUrlItems($website, '1')));
    }

    public function testGetUrlItems()
    {
        $website = $this->getWebsite(123);

        $fileName = 'test.xml';

        $file = $this->getFile($fileName);

        $this->filesystemAdapter->expects($this->once())
            ->method('getSitemapFiles')
            ->with($this->identicalTo($website), $this->isNull(), 'sitemap-index-*.xml*')
            ->willReturn([$file]);

        $absoluteUrl = 'http://test.com/sitemaps/123/test.xml';
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://test.com/subfolder/');

        $this->canonicalUrlGenerator->expects($this->once())
            ->method('createUrl')
            ->with('http://test.com', '/sitemaps/123/test.xml')
            ->willReturn($absoluteUrl);

        /** @var UrlItem[] $actual */
        $actual = iterator_to_array($this->provider->getUrlItems($website, '1'));
        $this->assertCount(1, $actual);

        $urlItem = reset($actual);
        $this->assertInstanceOf(UrlItem::class, $urlItem);
        $this->assertNotEmpty($urlItem->getLastModification());
        $this->assertEquals($absoluteUrl, $urlItem->getLocation());
        $this->assertEmpty($urlItem->getPriority());
        $this->assertEmpty($urlItem->getChangeFrequency());
    }
}
