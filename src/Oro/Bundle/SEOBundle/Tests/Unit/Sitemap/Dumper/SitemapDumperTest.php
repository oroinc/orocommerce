<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Dumper;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Bundle\SEOBundle\Sitemap\Website\WebsiteUrlProvidersServiceInterface;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SitemapDumperTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_PROVIDER_TYPE = 'product';
    private const STORAGE_TYPE = 'url';

    /** @var WebsiteUrlProvidersServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteUrlProvidersService;

    /** @var SitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $filesystemAdapter;

    /** @var SitemapStorageFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $sitemapStorageFactory;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var SitemapDumper */
    private $dumper;

    protected function setUp(): void
    {
        $this->websiteUrlProvidersService = $this->createMock(WebsiteUrlProvidersServiceInterface::class);
        $this->sitemapStorageFactory = $this->createMock(SitemapStorageFactory::class);
        $this->filesystemAdapter = $this->createMock(SitemapFilesystemAdapter::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dumper = new SitemapDumper(
            $this->websiteUrlProvidersService,
            $this->sitemapStorageFactory,
            $this->filesystemAdapter,
            $this->eventDispatcher,
            self::STORAGE_TYPE
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
                $event,
                sprintf('%s.%s', OnSitemapDumpFinishEvent::EVENT_NAME, self::STORAGE_TYPE)
            );
    }

    public function testDumpWithOneProviderWhenOneSitemapFileCreated()
    {
        $website = $this->getWebsite(123);
        $version = 1;

        $provider = $this->createMock(UrlItemsProviderInterface::class);
        $urlItem = new UrlItem('http://somedomain.com/firsturi');
        $provider->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$urlItem]);
        $providers[self::PRODUCT_PROVIDER_TYPE] = $provider;
        $this->websiteUrlProvidersService
            ->expects(static::once())
            ->method('getWebsiteProvidersIndexedByNames')
            ->with($website)
            ->willReturn($providers);

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
                $urlsStorage
            );

        $this->expectDispatchDumpFinishEvent($website, $version);

        $this->dumper->dump($website, $version, self::PRODUCT_PROVIDER_TYPE);
    }

    public function testDumpWithOneProviderWhenSeveralSitemapFileCreated()
    {
        $website = $this->getWebsite(123);
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
        $providers[self::PRODUCT_PROVIDER_TYPE] = $productProvider;
        $this->websiteUrlProvidersService
            ->expects(static::once())
            ->method('getWebsiteProvidersIndexedByNames')
            ->with($website)
            ->willReturn($providers);

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
                    $firstUrlsStorage
                ],
                [
                    $this->stringEndsWith(sprintf('sitemap-%s-2.xml', self::PRODUCT_PROVIDER_TYPE)),
                    $website,
                    $secondUrlsStorage
                ]
            );

        $this->expectDispatchDumpFinishEvent($website, $version);

        $this->dumper->dump($website, $version, self::PRODUCT_PROVIDER_TYPE);
    }

    public function testDumpWithAllProviders()
    {
        $website = $this->getWebsite(123);
        $version = 1;

        $productProvider = $this->createMock(UrlItemsProviderInterface::class);
        $productUrlItem = new UrlItem('http://somedomain.com/producturi');
        $productProvider
            ->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$productUrlItem]);

        $pageProvider = $this->createMock(UrlItemsProviderInterface::class);
        $pageUrlItem = new UrlItem('http://somedomain.com/pageuri');
        $pageProvider
            ->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$pageUrlItem]);

        $pageProviderType = 'page';
        $this->websiteUrlProvidersService
            ->expects($this->once())
            ->method('getWebsiteProvidersIndexedByNames')
            ->with($website)
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
                    $productUrlsStorage
                ],
                [
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', $pageProviderType)),
                    $website,
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
}
