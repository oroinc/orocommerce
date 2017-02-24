<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools;

use Oro\Bundle\SEOBundle\Model\DTO\UrlItem;
use Oro\Bundle\SEOBundle\Provider\UrlItemsProviderRegistry;
use Oro\Bundle\SEOBundle\Tools\SitemapDumper;
use Oro\Bundle\SEOBundle\Tools\SitemapFileWriterInterface;
use Oro\Bundle\SEOBundle\Tools\SitemapStorageFactory;
use Oro\Bundle\SEOBundle\Tools\SitemapUrlsStorageInterface;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;

class SitemapDumperTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_PROVIDER_TYPE = 'product';
    const KERNER_ROOT_DIR = '/kernel_root';
    const STORAGE_CONTENTS = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/><url><loc>http://some.com/uri</loc></url></urlset>
XML;

    /**
     * @var UrlItemsProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $providerRegistry;

    /**
     * @var SitemapFileWriterInterface|\PHPUnit_Framework_MockObject_MockObject
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
        $this->providerRegistry = $this->getMockBuilder(UrlItemsProviderRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sitemapStorageFactory = $this->getMockBuilder(SitemapStorageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sitemapFileWriter = $this->createMock(SitemapFileWriterInterface::class);

        $this->dumper = new SitemapDumper(
            $this->providerRegistry,
            $this->sitemapStorageFactory,
            $this->sitemapFileWriter,
            self::KERNER_ROOT_DIR
        );
    }

    public function testDumpWithOneProviderWhenOneSitemapFileCreated()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $productProvider = $this->createMock(UrlItemsProviderInterface::class);
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

        $urlsStorage = $this->configureUrlsStorageToAcceptOneUrlItemAndReturnContents($urlItem, self::STORAGE_CONTENTS);

        $this->sitemapStorageFactory
            ->expects($this->once())
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($urlsStorage);

        $this->sitemapFileWriter
            ->expects($this->once())
            ->method('saveSitemap')
            ->with(
                self::STORAGE_CONTENTS,
                $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE))
            );

        $this->assertStringStartsWith(
            $this->getSitemapsDir(),
            $this->dumper->dump($website, self::PRODUCT_PROVIDER_TYPE)
        );
    }

    public function testDumpWithOneProviderWhenFilesystemExceptionWasThrown()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $productProvider = $this->createMock(UrlItemsProviderInterface::class);
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
        $urlsStorage = $this->configureUrlsStorageToAcceptOneUrlItemAndReturnContents($urlItem, self::STORAGE_CONTENTS);

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
            ->with(
                self::STORAGE_CONTENTS,
                $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE))
            )
            ->willThrowException($exception);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->assertStringStartsWith(
            $this->getSitemapsDir(),
            $this->dumper->dump($website, self::PRODUCT_PROVIDER_TYPE)
        );
    }

    public function testDumpWithOneProviderWhenSeveralSitemapFileCreated()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

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

        $firstUrlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $firstUrlsStorage
            ->expects($this->exactly(3))
            ->method('addUrlItem')
            ->withConsecutive([$firstUrlItem], [$secondUrlItem], [$thirdUrlItem])
            ->willReturnOnConsecutiveCalls(true, true, false);

        $firstContents = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/><url><loc>http::/some.com/first_uri</loc></url></urlset>
XML;
        $firstUrlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($firstContents);

        $secondUrlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $secondUrlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->withConsecutive([$thirdUrlItem])
            ->willReturnOnConsecutiveCalls(true);

        $secondContents = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/><url><loc>http::/some.com/second_uri</loc></url></urlset>
XML;
        $secondUrlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($secondContents);

        $this->sitemapStorageFactory
            ->expects($this->exactly(2))
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($firstUrlsStorage, $secondUrlsStorage);

        $this->sitemapFileWriter
            ->expects($this->exactly(2))
            ->method('saveSitemap')
            ->withConsecutive(
                [
                    $firstContents,
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE))
                ],
                [
                    $secondContents,
                    $this->stringEndsWith(sprintf('sitemap-%s-2.xml', self::PRODUCT_PROVIDER_TYPE))
                ]
            );

        $this->assertStringStartsWith(
            $this->getSitemapsDir(),
            $this->dumper->dump($website, self::PRODUCT_PROVIDER_TYPE)
        );
    }

    public function testDumpWithAllProviders()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

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

        $productContents = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/><url><loc>http::/some.com/product_uri</loc></url></urlset>
XML;
        $productUrlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($productContents);

        $pageUrlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $pageUrlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->with($pageUrlItem)
            ->willReturn(true);

        $pageContents = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/><url><loc>http::/some.com/page_uri</loc></url></urlset>
XML;
        $pageUrlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($pageContents);

        $this->sitemapStorageFactory
            ->expects($this->exactly(2))
            ->method('createUrlsStorage')
            ->willReturnOnConsecutiveCalls($productUrlsStorage, $pageUrlsStorage);

        $this->sitemapFileWriter
            ->expects($this->exactly(2))
            ->method('saveSitemap')
            ->withConsecutive(
                [
                    $productContents,
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', self::PRODUCT_PROVIDER_TYPE))
                ],
                [
                    $pageContents,
                    $this->stringEndsWith(sprintf('sitemap-%s-1.xml', $pageProviderType))
                ]
            );

        $this->assertStringStartsWith($this->getSitemapsDir(), $this->dumper->dump($website));
    }

    /**
     * @return string
     */
    private function getSitemapsDir()
    {
        return sprintf('%s/%s/', self::KERNER_ROOT_DIR, 'sitemaps');
    }

    /**
     * @param UrlItem $urlItem
     * @param string $content
     * @return SitemapUrlsStorageInterface
     */
    private function configureUrlsStorageToAcceptOneUrlItemAndReturnContents(UrlItem $urlItem, $content)
    {
        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $urlsStorage
            ->expects($this->once())
            ->method('addUrlItem')
            ->withConsecutive($urlItem)
            ->willReturnOnConsecutiveCalls(true);

        $urlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturnOnConsecutiveCalls($content);

        return $urlsStorage;
    }
}
