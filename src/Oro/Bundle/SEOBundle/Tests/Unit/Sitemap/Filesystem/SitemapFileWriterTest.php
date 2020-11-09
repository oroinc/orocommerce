<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Filesystem;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFileWriter;
use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class SitemapFileWriterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Filesystem|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileSystem;
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var SitemapFileWriter
     */
    private $sitemapFileWriter;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    protected function setUp()
    {
        $this->fileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->fileManager = $this->createMock(FileManager::class);

        $this->sitemapFileWriter = new SitemapFileWriter($this->fileSystem, $this->logger);
        $this->sitemapFileWriter->setFileManager($this->fileManager);
    }

    public function testSaveSitemap()
    {
        $stringData = 'some_string_data';

        $filePath = '/some/path/file-1.xml';
        $this->fileManager
            ->expects($this->once())
            ->method('writeToStorage')
            ->with($stringData, $filePath);

        $this->logger
            ->expects($this->never())
            ->method('debug');

        $this->assertEquals($filePath, $this->sitemapFileWriter->saveSitemap($stringData, $filePath));
    }

    public function testSaveSitemapWhenDumpFileThrowsException()
    {
        $filePath = '/some/path/file-1.xml';

        $ioExceptionMessage = '';
        $exception = new IOException($ioExceptionMessage);

        $this->fileManager
            ->expects($this->once())
            ->method('writeToStorage')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($ioExceptionMessage);

        $this->expectException(SitemapFileWriterException::class);
        $this->expectExceptionMessage('An error occurred while writing sitemap to ' . $filePath);

        $this->sitemapFileWriter->saveSitemap('some_string_data', $filePath);
    }
}
