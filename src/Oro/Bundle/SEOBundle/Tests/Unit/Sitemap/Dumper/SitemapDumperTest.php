<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Dumper;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistry;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\SEO\Provider\VersionAwareUrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SitemapDumperTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_PROVIDER_TYPE = 'product';
    const STORAGE_TYPE = 'url';

    /**
     * @var UrlItemsProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $providerRegistry;

    /**
     * @var SitemapFilesystemAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystemAdapter;

    /**
     * @var SitemapStorageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sitemapStorageFactory;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var SitemapDumper
     */
    private $dumper;

    protected function setUp()
    {
        $this->providerRegistry = $this->getMockBuilder(UrlItemsProviderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sitemapStorageFactory = $this->getMockBuilder(SitemapStorageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystemAdapter = $this->getMockBuilder(SitemapFilesystemAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dumper = new SitemapDumper(
            $this->providerRegistry,
            $this->sitemapStorageFactory,
            $this->filesystemAdapter,
            $this->eventDispatcher,
            self::STORAGE_TYPE
        );
    }

    public function testDumpWithOneProviderWhenOneSitemapFileCreated()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        /** @var UrlItemsProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(UrlItemsProviderInterface::class);
        $urlItem = new UrlItem('http://somedomain.com/firsturi');
        $provider->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$urlItem]);

        $this->providerRegistry
            ->expects($this->once())
            ->method('getProviderByName')
            ->with(self::PRODUCT_PROVIDER_TYPE)
            ->willReturn($provider);

        /** @var SitemapStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapStorageInterface::class);
        $urlsStorage->expects($this->once())
            ->method('addUrlItem')
            ->with($urlItem)
            ->willReturn(true);

        $this->sitemapStorageFactory
            ->expects($this->once())
            ->method('createUrlsStorage')
            ->willReturn($urlsStorage);

        $this->filesystemAdapter
            ->expects($this->once())
            ->method('dumpSitemapStorage')
            ->with(
                $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE)),
                $website,
                $version,
                $urlsStorage
            );

        $this->expectDispatchDumpFinishEvent($website, $version);

        $this->dumper->dump($website, $version, self::PRODUCT_PROVIDER_TYPE);
    }

    public function testDumpWithOneProviderWhenSeveralSitemapFileCreated()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $productProvider = $this->createMock(UrlItemsProviderInterface::class);
        $firstUrlItem = new UrlItem('http://somedomain.com/firsturi');
        $secondUrlItem = new UrlItem('http://somedomain.com/seconduri');
        $thirdUrlItem = new UrlItem('http://somedomain.com/thirduri');
        $productProvider
            ->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$firstUrlItem, $secondUrlItem, $thirdUrlItem]);

        $this->providerRegistry
            ->expects($this->once())
            ->method('getProviderByName')
            ->willReturn($productProvider);

        $firstUrlsStorage = $this->createMock(SitemapStorageInterface::class);

        $firstUrlsStorage
            ->expects($this->exactly(3))
            ->method('addUrlItem')
            ->withConsecutive([$firstUrlItem], [$secondUrlItem], [$thirdUrlItem])
            ->willReturnOnConsecutiveCalls(true, true, false);

        $secondUrlsStorage = $this->createMock(SitemapStorageInterface::class);

        $secondUrlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->withConsecutive([$thirdUrlItem])
            ->willReturnOnConsecutiveCalls(true);

        $this->sitemapStorageFactory
            ->expects($this->exactly(2))
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($firstUrlsStorage, $secondUrlsStorage);

        $this->filesystemAdapter
            ->expects($this->exactly(2))
            ->method('dumpSitemapStorage')
            ->withConsecutive(
                [
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE)),
                    $website,
                    $version,
                    $firstUrlsStorage
                ],
                [
                    $this->stringEndsWith(sprintf('sitemap-%s-2.xml', self::PRODUCT_PROVIDER_TYPE)),
                    $website,
                    $version,
                    $secondUrlsStorage
                ]
            );

        $this->expectDispatchDumpFinishEvent($website, $version);

        $this->dumper->dump($website, $version, self::PRODUCT_PROVIDER_TYPE);
    }

    public function testDumpWithAllProviders()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = 1;

        $productProvider = $this->createMock(VersionAwareUrlItemsProviderInterface::class);
        $productUrlItem = new UrlItem('http://somedomain.com/producturi');
        $productProvider
            ->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$productUrlItem]);
        $productProvider->expects($this->once())
            ->method('setVersion')
            ->with($version);

        $pageProvider = $this->createMock(UrlItemsProviderInterface::class);
        $pageUrlItem = new UrlItem('http://somedomain.com/pageuri');
        $pageProvider
            ->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$pageUrlItem]);

        $pageProviderType = 'page';
        $this->providerRegistry
            ->expects($this->once())
            ->method('getProviders')
            ->willReturn([self::PRODUCT_PROVIDER_TYPE => $productProvider, $pageProviderType => $pageProvider]);

        $productUrlsStorage = $this->createMock(SitemapStorageInterface::class);
        $productUrlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->with($productUrlItem)
            ->willReturn(true);

        $pageUrlsStorage = $this->createMock(SitemapStorageInterface::class);
        $pageUrlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->with($pageUrlItem)
            ->willReturn(true);

        $this->sitemapStorageFactory
            ->expects($this->exactly(2))
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($productUrlsStorage, $pageUrlsStorage);

        $this->filesystemAdapter
            ->expects($this->exactly(2))
            ->method('dumpSitemapStorage')
            ->withConsecutive(
                [
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE)),
                    $website,
                    $version,
                    $productUrlsStorage
                ],
                [
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', $pageProviderType)),
                    $website,
                    $version,
                    $pageUrlsStorage
                ]
            );

        $this->expectDispatchDumpFinishEvent($website, $version);

        $this->dumper->dump($website, $version);
    }

    public function testGetFilenamePatternAllTypes()
    {
        $this->assertEquals('sitemap-*-*.xml*', SitemapDumper::getFilenamePattern());
    }

    public function testGetFilenamePatternSpecificType()
    {
        $this->assertEquals('sitemap-index-*.xml*', SitemapDumper::getFilenamePattern('index'));
    }

    /**
     * @param WebsiteInterface $website
     * @param string $version
     */
    private function expectDispatchDumpFinishEvent($website, $version)
    {
        $event = new OnSitemapDumpFinishEvent($website, $version);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                sprintf(
                    '%s.%s',
                    OnSitemapDumpFinishEvent::EVENT_NAME,
                    self::STORAGE_TYPE
                ),
                $event
            );
    }
}
