<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools;

use Oro\Bundle\SEOBundle\Tools\SitemapFileWriter;
use Oro\Bundle\SEOBundle\Tools\SitemapFileWriterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class SitemapFileWriterFactoryTest extends \PHPUnit_Framework_TestCase
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
     * @var SitemapFileWriterFactory
     */
    private $sitemapFileWriterFactory;

    protected function setUp()
    {
        $this->fileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sitemapFileWriterFactory = new SitemapFileWriterFactory($this->fileSystem, $this->logger);
    }

    public function testSaveSitemapWhenZipIsNotAvailable()
    {
        if (extension_loaded('zip')) {
            $this->markTestSkipped('This test is applicable when zip extension has not been loaded');
        }

        $sitemapFileWriter = $this->sitemapFileWriterFactory->create();

        $this->assertInstanceOf(SitemapFileWriter::class, $sitemapFileWriter);
        $this->assertEmpty($sitemapFileWriter->getZipArchive());
    }

    public function testSaveSitemapWhenZipIsNotAvailableAndDumpFileThrowsException()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('This test is applicable when zip extension has been loaded');
        }

        $sitemapFileWriter = $this->sitemapFileWriterFactory->create();

        $this->assertInstanceOf(SitemapFileWriter::class, $sitemapFileWriter);
        $this->assertInstanceOf(\ZipArchive::class, $sitemapFileWriter->getZipArchive());
    }
}
