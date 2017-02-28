<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Filesystem;

use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFileWriter;
use Oro\Bundle\SEOBundle\Sitemap\Exception\SitemapFileWriterException;
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

    public function testSaveSitemap()
    {
        $stringData = 'some_string_data';

        $filePath = '/some/path/file-1.xml';
        $this->fileSystem
            ->expects($this->once())
            ->method('dumpFile')
            ->with($filePath, $stringData);

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

        $this->fileSystem
            ->expects($this->once())
            ->method('dumpFile')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($ioExceptionMessage);

        $this->expectException(SitemapFileWriterException::class);
        $this->expectExceptionMessage($ioExceptionMessage);

        $this->sitemapFileWriter->saveSitemap('some_string_data', $filePath);
    }
}
