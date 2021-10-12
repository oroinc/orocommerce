<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Filesystem;

use Oro\Bundle\SEOBundle\Sitemap\Filesystem\GzipSitemapFileWriter;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFileWriterInterface;

class GzipSitemapFileWriterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SitemapFileWriterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $innerSitemapFileWriter;

    /**
     * @var GzipSitemapFileWriter
     */
    private $sitemapFileWriter;

    protected function setUp(): void
    {
        $this->innerSitemapFileWriter = $this->createMock(SitemapFileWriterInterface::class);

        $this->sitemapFileWriter = new GzipSitemapFileWriter($this->innerSitemapFileWriter);
    }

    public function testSaveSitemap()
    {
        if (!extension_loaded('zlib')) {
            $this->markTestSkipped('This test need zlib extension loaded');
        }

        $content = '<?xml ?>';
        $path = '/some/path/sitemap-index-1.xml';
        $expectedPath = sprintf('%s.gz', $path);

        $this->innerSitemapFileWriter
            ->expects($this->once())
            ->method('saveSitemap')
            ->with($this->anything(), $expectedPath);

        $this->sitemapFileWriter->saveSitemap($content, $path);
    }

    public function testSaveSitemapSkippedCompression()
    {
        $content = '<?xml ?>';
        $path = '/some/path/sitemap-index-1.xml';

        $this->innerSitemapFileWriter
            ->expects($this->once())
            ->method('saveSitemap')
            ->with($this->anything(), $path);

        $this->sitemapFileWriter->skipCompressionForProviderTypes(['index']);
        $this->sitemapFileWriter->saveSitemap($content, $path);
    }
}
