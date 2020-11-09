<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Gaufrette\File;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Provider\SitemapFilesProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Website\WebsiteInterface;

class SitemapFilesProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var SitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filesystemAdapter;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var string
     */
    private $webPath;

    /**
     * @var SitemapFilesProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->filesystemAdapter = $this->getMockBuilder(SitemapFilesystemAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->canonicalUrlGenerator = $this->getMockBuilder(CanonicalUrlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->webPath = '/sitemaps';

        $this->provider = new SitemapFilesProvider(
            $this->filesystemAdapter,
            $this->canonicalUrlGenerator,
            $this->webPath
        );
    }

    public function testGetUrlItemsNoFiles()
    {
        /** @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = '1';

        $this->filesystemAdapter->expects($this->once())
            ->method('getSitemapFilesForWebsite')
            ->with($website)
            ->willReturn([]);

        $this->canonicalUrlGenerator->expects($this->never())
            ->method($this->anything());

        $this->assertEquals([], iterator_to_array($this->provider->getUrlItems($website, $version)));
    }

    public function testGetUrlItems()
    {
        /** @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject $website */
        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $version = '42';

        $fileName = 'test.xml';

        $file = $this->createMock(File::class);
        $file->expects($this->any())
            ->method('getName')
            ->willReturn($fileName);
        $file->expects($this->any())
            ->method('getMTime')
            ->willReturn(time());

        $this->filesystemAdapter->expects($this->once())
            ->method('getSitemapFilesForWebsite')
            ->with($website, null, 'sitemap-index-*.xml*')
            ->willReturn([$file]);

        $absoluteUrl = 'http://test.com/sitemaps/1/actual/test.xml';
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://test.com/subfolder/');

        $this->canonicalUrlGenerator->expects($this->once())
            ->method('createUrl')
            ->with('http://test.com', '/sitemaps/1/actual/test.xml')
            ->willReturn($absoluteUrl);

        $actual = iterator_to_array($this->provider->getUrlItems($website, $version));
        $this->assertCount(1, $actual);
        /** @var UrlItem $urlItem */
        $urlItem = reset($actual);
        $this->assertInstanceOf(UrlItem::class, $urlItem);
        $this->assertNotEmpty($urlItem->getLastModification());
        $this->assertEquals($absoluteUrl, $urlItem->getLocation());
        $this->assertEmpty($urlItem->getPriority());
        $this->assertEmpty($urlItem->getChangeFrequency());
    }
}
