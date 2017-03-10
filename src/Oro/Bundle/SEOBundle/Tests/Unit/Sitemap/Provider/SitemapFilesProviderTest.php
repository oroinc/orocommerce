<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Provider\SitemapFilesProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Finder\Finder;

class SitemapFilesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SitemapFilesystemAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystemAdapter;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit_Framework_MockObject_MockObject
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
        /** @var WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = '1';

        $this->filesystemAdapter->expects($this->once())
            ->method('getSitemapFiles')
            ->with($website, $version)
            ->willReturn(new \ArrayIterator());

        $this->canonicalUrlGenerator->expects($this->never())
            ->method($this->anything());

        $this->assertEquals([], iterator_to_array($this->provider->getUrlItems($website, $version)));
    }

    public function testGetUrlItems()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $version = 'actual';

        $fileName = 'test.xml';

        $file = $this->getMockBuilder(\SplFileInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $file->expects($this->once())
            ->method('getFilename')
            ->willReturn($fileName);
        $file->expects($this->once())
            ->method('getMTime')
            ->willReturn(time());

        /** @var Finder|\PHPUnit_Framework_MockObject_MockObject $finder */
        $finder = $this->getMockBuilder(Finder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $finder->expects($this->once())
            ->method('notName')
            ->with('sitemap-index-*.xml*')
            ->willReturnSelf();
        $this->configureIteratorMock($finder, [$file]);

        $this->filesystemAdapter->expects($this->once())
            ->method('getSitemapFiles')
            ->with($website, $version)
            ->willReturn($finder);

        $absoluteUrl = 'http://test.com/sitemaps/1/0/test.xml';
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with('/sitemaps/1/0/test.xml', $website)
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

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $mock
     * @param array $items
     */
    private function configureIteratorMock(\PHPUnit_Framework_MockObject_MockObject $mock, array $items)
    {
        $iterator = new \ArrayIterator($items);

        $mock->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);
        $mock->expects($this->any())
            ->method('count')
            ->willReturn($iterator->count());
    }
}
