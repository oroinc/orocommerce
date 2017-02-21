<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Provider\SitemapUrlProviderRegistry;
use Oro\Bundle\SEOBundle\Tools\SitemapDumper;
use Oro\Bundle\SEOBundle\Tools\SitemapFileWriter;
use Oro\Bundle\SEOBundle\Tools\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Tools\SitemapUrlsStorageInterface;
use Oro\Component\SEO\Provider\SitemapUrlProviderInterface;
use Oro\Component\Website\WebsiteInterface;

class SitemapDumperTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_PROVIDER_TYPE = 'product';

    /**
     * @var SitemapUrlProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $providerRegistry;

    /**
     * @var SitemapFileWriter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sitemapFileWriter;

    /**
     * @var SitemapStorageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sitemapStorageFactory;

    /**
     * @var SitemapDumper
     */
    private $dumper;

    protected function setUp()
    {
        $this->providerRegistry = $this->getMockBuilder(SitemapUrlProviderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sitemapStorageFactory = $this->getMockBuilder(SitemapStorageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sitemapFileWriter = $this->createMock(SitemapFileWriter::class);
        $this->dumper = new SitemapDumper(
            $this->providerRegistry,
            $this->sitemapStorageFactory,
            $this->sitemapFileWriter
        );
    }

    public function testDumpWithOneProviderWhenOneSitemapFileCreated()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $productProvider = $this->createMock(SitemapUrlProviderInterface::class);
        $urlItem = new UrlItem('http://somedomain.com/firsturi');
        $productProvider
            ->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$urlItem]);

        $this->providerRegistry
            ->expects($this->once())
            ->method('getProviderByName')
            ->willReturn($productProvider);

        $urlsStorage = $this->configureUrlsStorageToAcceptOneUrlItemAndReturnContents($urlItem);
        $this->sitemapStorageFactory
            ->expects($this->once())
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($urlsStorage);

        $this->sitemapFileWriter
            ->expects($this->once())
            ->method('saveSitemap')
            ->withConsecutive(
                [
                    $urlsStorage,
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE))
                ]
            );

        $this->dumper->dump($website, self::PRODUCT_PROVIDER_TYPE);
    }

    public function testDumpWithOneProviderWhenFilesystemExceptionWasThrown()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $productProvider = $this->createMock(SitemapUrlProviderInterface::class);
        $urlItem = new UrlItem('http://somedomain.com/firsturi');
        $productProvider
            ->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$urlItem]);

        $this->providerRegistry
            ->expects($this->once())
            ->method('getProviderByName')
            ->willReturn($productProvider);

        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->configureUrlsStorageToAcceptOneUrlItemAndReturnContents($urlItem);

        $urlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->withConsecutive([$urlItem])
            ->willReturnOnConsecutiveCalls(true);

        $this->sitemapStorageFactory
            ->expects($this->once())
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($urlsStorage);

        $exceptionMessage = 'Some message';
        $exception = new \Exception($exceptionMessage);
        $this->sitemapFileWriter
            ->expects($this->once())
            ->method('saveSitemap')
            ->willThrowException($exception);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->dumper->dump($website, self::PRODUCT_PROVIDER_TYPE);
    }

    public function testDumpWithOneProviderWhenSeveralSitemapFileCreated()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $productProvider = $this->createMock(SitemapUrlProviderInterface::class);
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

        $firstUrlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $firstUrlsStorage
            ->expects($this->exactly(3))
            ->method('addUrlItem')
            ->withConsecutive([$firstUrlItem], [$secondUrlItem], [$thirdUrlItem])
            ->willReturnOnConsecutiveCalls(true, true, false);

        $secondUrlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $secondUrlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->withConsecutive([$thirdUrlItem])
            ->willReturnOnConsecutiveCalls(true);

        $this->sitemapStorageFactory
            ->expects($this->exactly(2))
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($firstUrlsStorage, $secondUrlsStorage);

        $this->sitemapFileWriter
            ->expects($this->exactly(2))
            ->method('saveSitemap')
            ->withConsecutive(
                [
                    $firstUrlsStorage,
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE))
                ],
                [
                    $secondUrlsStorage,
                    $this->stringEndsWith(sprintf('sitemap-%s-2.xml', self::PRODUCT_PROVIDER_TYPE))
                ]
            );

        $this->dumper->dump($website, self::PRODUCT_PROVIDER_TYPE);
    }

    public function testDumpWithAllProviders()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $productProvider = $this->createMock(SitemapUrlProviderInterface::class);
        $productUrlItem = new UrlItem('http://somedomain.com/producturi');
        $productProvider
            ->expects($this->once())
            ->method('getUrlItems')
            ->with($website)
            ->willReturn([$productUrlItem]);

        $pageProvider = $this->createMock(SitemapUrlProviderInterface::class);
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

        $productUrlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $productUrlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->with($productUrlItem)
            ->willReturn(true);

        $pageUrlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $pageUrlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->with($pageUrlItem)
            ->willReturn(true);

        $this->sitemapStorageFactory
            ->expects($this->exactly(2))
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($productUrlsStorage, $pageUrlsStorage);

        $this->sitemapFileWriter
            ->expects($this->exactly(2))
            ->method('saveSitemap')
            ->withConsecutive(
                [
                    $productUrlsStorage,
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE))
                ],
                [
                    $pageUrlsStorage,
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', $pageProviderType))
                ]
            );

        $this->dumper->dump($website);
    }

    /**
     * @param UrlItem $urlItem
     * @return SitemapUrlsStorageInterface
     */
    private function configureUrlsStorageToAcceptOneUrlItemAndReturnContents(UrlItem $urlItem)
    {
        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $urlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->withConsecutive($urlItem)
            ->willReturnOnConsecutiveCalls(true);

        return $urlsStorage;
    }
}
