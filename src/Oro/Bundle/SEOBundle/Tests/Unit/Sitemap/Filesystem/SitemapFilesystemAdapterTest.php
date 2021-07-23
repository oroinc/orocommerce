<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Filesystem;

use Gaufrette\File;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFileWriterInterface;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Component\Website\WebsiteInterface;

class SitemapFilesystemAdapterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var SitemapFileWriterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileWriter;

    /** @var SitemapFilesystemAdapter */
    private $sitemapFilesystemAdapter;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->fileWriter = $this->createMock(SitemapFileWriterInterface::class);

        $this->sitemapFilesystemAdapter = new SitemapFilesystemAdapter(
            $this->fileManager,
            $this->fileWriter
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

    private function getFile(string $fileName): File
    {
        $file = $this->createMock(File::class);
        $file->expects($this->any())
            ->method('getName')
            ->willReturn($fileName);
        $file->expects($this->any())
            ->method('getMtime')
            ->willReturn(time());

        return $file;
    }

    /**
     * @param File[] $files
     *
     * @return array
     */
    private function getFileNames(array $files): array
    {
        $fileNames = array_map(
            function (File $file) {
                return $file->getName();
            },
            $files
        );
        sort($fileNames);

        return $fileNames;
    }

    public function testDumpSitemapStorage()
    {
        $website = $this->getWebsite(123);
        $filename = 'sitemap-test-1.xml';
        $content = 'test';

        $storage = $this->createMock(SitemapStorageInterface::class);
        $storage->expects($this->once())
            ->method('getContents')
            ->willReturn($content);
        $storage->expects($this->once())
            ->method('getUrlItemsCount')
            ->willReturn(1);

        $this->fileWriter->expects($this->once())
            ->method('saveSitemap')
            ->with($content, $website->getId() . DIRECTORY_SEPARATOR . $filename);

        $this->sitemapFilesystemAdapter->dumpSitemapStorage($filename, $website, $storage);
    }

    public function testDumpSitemapStorageWithoutItems()
    {
        $website = $this->getWebsite(123);
        $filename = 'sitemap-test-1.xml';

        $storage = $this->createMock(SitemapStorageInterface::class);
        $storage->expects($this->once())
            ->method('getUrlItemsCount')
            ->willReturn(0);

        $storage->expects($this->never())
            ->method('getContents');
        $this->fileWriter->expects($this->never())
            ->method('saveSitemap');

        $this->sitemapFilesystemAdapter->dumpSitemapStorage($filename, $website, $storage);
    }

    public function testGetSitemapFilesUnknownPath()
    {
        $website = $this->getWebsite(123);
        $this->assertEmpty($this->sitemapFilesystemAdapter->getSitemapFiles($website));
    }

    public function testGetSitemapFilesWithoutPattern()
    {
        $website = $this->getWebsite(123);
        $filePrefix = $website->getId() . DIRECTORY_SEPARATOR;

        $this->fileManager->expects($this->once())
            ->method('findFiles')
            ->with($filePrefix)
            ->willReturn([
                $filePrefix . 'sitemap-page-1.xml',
                $filePrefix . 'sitemap-product-1.xml'
            ]);
        $this->fileManager->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                [$filePrefix . 'sitemap-page-1.xml'],
                [$filePrefix . 'sitemap-product-1.xml'],
            )
            ->willReturnCallback(function ($fileName) {
                return $this->getFile($fileName);
            });

        $files = $this->sitemapFilesystemAdapter->getSitemapFiles($website);

        $this->assertEquals(
            [
                $filePrefix . 'sitemap-page-1.xml',
                $filePrefix . 'sitemap-product-1.xml'
            ],
            $this->getFileNames($files)
        );
    }

    public function testGetSitemapFilesWithPattern()
    {
        $website = $this->getWebsite(123);
        $filePrefix = $website->getId() . DIRECTORY_SEPARATOR;

        $this->fileManager->expects($this->once())
            ->method('findFiles')
            ->with($filePrefix)
            ->willReturn([
                $filePrefix . 'sitemap-page-1.xml',
                $filePrefix . 'sitemap-product-1.xml',
                $filePrefix . 'sitemap-product-2.xml',
                $filePrefix . 'sitemap-product-3.xml'
            ]);
        $this->fileManager->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                [$filePrefix . 'sitemap-product-1.xml'],
                [$filePrefix . 'sitemap-product-3.xml']
            )
            ->willReturnCallback(function ($fileName) {
                return $this->getFile($fileName);
            });

        $files = $this->sitemapFilesystemAdapter->getSitemapFiles(
            $website,
            'sitemap-product-*.xml*',
            'sitemap-product-2.xml'
        );

        $this->assertEquals(
            [
                $filePrefix . 'sitemap-product-1.xml',
                $filePrefix . 'sitemap-product-3.xml'
            ],
            $this->getFileNames($files)
        );
    }
}
