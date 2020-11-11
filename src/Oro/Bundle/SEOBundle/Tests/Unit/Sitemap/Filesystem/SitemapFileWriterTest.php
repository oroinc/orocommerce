<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Filesystem;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFileWriter;
use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;
use Psr\Log\LoggerInterface;

class SitemapFileWriterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var SitemapFileWriter */
    private $sitemapFileWriter;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sitemapFileWriter = new SitemapFileWriter($this->fileManager, $this->logger);
    }

    public function testSaveSitemap()
    {
        $stringData = 'some_string_data';

        $filePath = '/some/path/file-1.xml';
        $this->fileManager->expects($this->once())
            ->method('writeToStorage')
            ->with($stringData, $filePath);

        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($filePath, $this->sitemapFileWriter->saveSitemap($stringData, $filePath));
    }

    public function testSaveSitemapWhenDumpFileThrowsException()
    {
        $filePath = '/some/path/file-1.xml';

        $exceptionMessage = 'some error';
        $exception = new \Exception($exceptionMessage);

        $this->fileManager->expects($this->once())
            ->method('writeToStorage')
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('debug')
            ->with($exceptionMessage);

        $this->expectException(SitemapFileWriterException::class);
        $this->expectExceptionMessage('An error occurred while writing sitemap to ' . $filePath);

        $this->sitemapFileWriter->saveSitemap('some_string_data', $filePath);
    }
}
