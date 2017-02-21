<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools;

use Oro\Bundle\SEOBundle\Tools\SitemapFileWriter;
use Oro\Bundle\SEOBundle\Tools\Exception\SitemapFileWriterException;
use Oro\Bundle\SEOBundle\Tools\SitemapUrlsStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class SitemapFileWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileSystem;
    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var SitemapFileWriter
     */
    private $sitemapFileWriter;

    protected function setUp()
    {
        $this->fileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sitemapFileWriter = new SitemapFileWriter($this->fileSystem, $this->logger);
    }

    public function testSetAndGetZipArchive()
    {
        $this->assertEmpty($this->sitemapFileWriter->getZipArchive());

        $zipArchive = new \stdClass();
        $this->sitemapFileWriter->setZipArchive($zipArchive);

        $this->assertSame($zipArchive, $this->sitemapFileWriter->getZipArchive());
    }

    public function testSaveSitemapWhenZipIsNotAvailable()
    {
        $urlStorageXml= <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>
XML;

        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);
        $urlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($urlStorageXml);

        $filePath = '/some/path/file-1.xml';
        $this->fileSystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with($filePath, $urlStorageXml);

        $this->assertEquals($filePath, $this->sitemapFileWriter->saveSitemap($urlsStorage, $filePath));
    }

    public function testSaveSitemapWhenZipIsNotAvailableAndDumpFileThrowsException()
    {
        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $filePath = '/some/path/file-1.xml';

        $ioExceptionMessage = '';
        $exception = new IOException($ioExceptionMessage);

        $this->fileSystem
            ->expects($this->once())
            ->method('dumpFile')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($ioExceptionMessage);

        $this->expectException(\Oro\Bundle\SEOBundle\Tools\Exception\SitemapFileWriterException::class);
        $this->expectExceptionMessage($ioExceptionMessage);

        $this->sitemapFileWriter->saveSitemap($urlsStorage, $filePath);
    }

    public function testSaveSitemapWhenZipIsAvailableAndOpenArchiveFails()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('This test is applicable when zip extension has been loaded');
        }

        $filePath = '/some/path/file-1.xml';
        $expectedFilePath = $filePath . '.zip';

        $zipArchive = $this->getMockBuilder(\ZipArchive::class)
            ->disableOriginalConstructor()
            ->getMock();

        $zipArchive
            ->expects($this->once())
            ->method('open')
            ->with($expectedFilePath, \ZipArchive::CREATE)
            ->willReturn(false);

        $this->sitemapFileWriter->setZipArchive($zipArchive);

        $this->expectException(\Oro\Bundle\SEOBundle\Tools\Exception\SitemapFileWriterException::class);
        $this->expectExceptionMessage(sprintf('Cannot open archive for sitemap %s', $expectedFilePath));

        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);

        $this->sitemapFileWriter->saveSitemap($urlsStorage, $filePath);
    }

    public function testSaveSitemapWhenZipIsAvailableAndAddSitemapDataFails()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('This test is applicable when zip extension has been loaded');
        }

        $fileName = 'file-1.xml';
        $filePath = sprintf('/some/path/%s', $fileName);
        $expectedFilePath = $filePath . '.zip';

        $urlStorageXml= <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>
XML;

        $zipArchive = $this->getMockBuilder(\ZipArchive::class)
            ->disableOriginalConstructor()
            ->getMock();

        $zipArchive
            ->expects($this->once())
            ->method('open')
            ->with($expectedFilePath, \ZipArchive::CREATE)
            ->willReturn(true);

        $zipArchive
            ->expects($this->once())
            ->method('addFromString')
            ->with($fileName, $urlStorageXml)
            ->willReturn(false);

        $this->sitemapFileWriter->setZipArchive($zipArchive);

        $this->expectException(\Oro\Bundle\SEOBundle\Tools\Exception\SitemapFileWriterException::class);
        $this->expectExceptionMessage(sprintf('Cannot add data to archive for sitemap %s', $expectedFilePath));

        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);
        $urlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($urlStorageXml);

        $this->sitemapFileWriter->saveSitemap($urlsStorage, $filePath);
    }

    public function testSaveSitemapWhenZipIsAvailableAndCloseArchiveFails()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('This test is applicable when zip extension has been loaded');
        }

        $fileName = 'file-1.xml';
        $filePath = sprintf('/some/path/%s', $fileName);
        $expectedFilePath = $filePath . '.zip';

        $urlStorageXml= <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>
XML;

        $zipArchive = $this->getMockBuilder(\ZipArchive::class)
            ->disableOriginalConstructor()
            ->getMock();

        $zipArchive
            ->expects($this->once())
            ->method('open')
            ->with($expectedFilePath, \ZipArchive::CREATE)
            ->willReturn(true);

        $zipArchive
            ->expects($this->once())
            ->method('addFromString')
            ->with($fileName, $urlStorageXml)
            ->willReturn(true);

        $zipArchive
            ->expects($this->once())
            ->method('close')
            ->willReturn(false);

        $this->sitemapFileWriter->setZipArchive($zipArchive);

        $this->expectException(SitemapFileWriterException::class);
        $this->expectExceptionMessage(sprintf('Cannot save archive for sitemap %s', $expectedFilePath));

        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);
        $urlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($urlStorageXml);

        $this->sitemapFileWriter->saveSitemap($urlsStorage, $filePath);
    }

    public function testSaveSitemapWhenZipIsAvailable()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('This test is applicable when zip extension has been loaded');
        }

        $filePath = '/some/path/file-1.xml';
        $expectedFilePath = $filePath . '.zip';

        $urlStorageXml= <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>
XML;

        $zipArchive = $this->getMockBuilder(\ZipArchive::class)
            ->disableOriginalConstructor()
            ->getMock();

        $zipArchive
            ->expects($this->once())
            ->method('open')
            ->with($expectedFilePath, \ZipArchive::CREATE)
            ->willReturn(true);

        $zipArchive
            ->expects($this->once())
            ->method('addFromString')
            ->with('file-1.xml', $urlStorageXml)
            ->willReturn(true);

        $zipArchive
            ->expects($this->once())
            ->method('close')
            ->willReturn(true);

        $this->sitemapFileWriter->setZipArchive($zipArchive);

        /** @var SitemapUrlsStorageInterface|\PHPUnit_Framework_MockObject_MockObject $urlsStorage */
        $urlsStorage = $this->createMock(SitemapUrlsStorageInterface::class);
        $urlsStorage
            ->expects($this->once())
            ->method('getContents')
            ->willReturn($urlStorageXml);

        $this->assertEquals($expectedFilePath, $this->sitemapFileWriter->saveSitemap($urlsStorage, $filePath));
    }
}
