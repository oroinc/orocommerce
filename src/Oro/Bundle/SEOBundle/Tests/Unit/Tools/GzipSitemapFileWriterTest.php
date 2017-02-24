<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Tools;

use Oro\Bundle\SEOBundle\Tools\GzipSitemapFileWriter;
use Oro\Bundle\SEOBundle\Tools\SitemapFileWriterInterface;

class GzipSitemapFileWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SitemapFileWriterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $innerSitemapFileWriter;

    /**
     * @var GzipSitemapFileWriter
     */
    private $sitemapFileWriter;

    protected function setUp()
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
        $path = '/some/path/sitemap.xml';
        $expectedPath = sprintf('%s.gz', $path);

        $this->innerSitemapFileWriter
            ->expects($this->once())
            ->method('saveSitemap')
            ->with($this->anything(), $expectedPath);

        $this->sitemapFileWriter->saveSitemap($content, $path);
    }
}
