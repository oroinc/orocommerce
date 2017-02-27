<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Filesystem;

use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFileWriterInterface;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SitemapFilesystemAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;

    /**
     * @var SitemapFileWriterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileWriter;

    /**
     * @var string
     */
    private $path;

    /**
     * @var SitemapFilesystemAdapter
     */
    private $adapter;

    protected function setUp()
    {
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileWriter = $this->createMock(SitemapFileWriterInterface::class);
        $this->path = realpath(__DIR__ . '/fixtures');

        $this->adapter = new SitemapFilesystemAdapter(
            $this->filesystem,
            $this->fileWriter,
            $this->path
        );
    }

    public function testDumpSitemapStorage()
    {
        $website = $this->getConfiguredWebsite();

        $content = 'test';
        /** @var SitemapStorageInterface||\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->createMock(SitemapStorageInterface::class);
        $storage->expects($this->once())
            ->method('getContents')
            ->willReturn($content);

        $filename = 'sitemap-test-1.xml';
        $version = 'actual';
        $testPath = $this->getPath(1, $version);
        $this->filesystem->expects($this->once())
            ->method('mkdir')
            ->with($testPath);
        $this->fileWriter->expects($this->once())
            ->method('saveSitemap')
            ->with($content, $testPath . DIRECTORY_SEPARATOR . $filename);

        $this->adapter->dumpSitemapStorage($filename, $website, $version, $storage);
    }

    public function testMakeActualNonExistingVersion()
    {
        $website = $this->getConfiguredWebsite();
        $version = '1';
        $versionPath = $this->getPath(1, $version);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($versionPath)
            ->willReturn(false);
        $this->filesystem->expects($this->never())
            ->method('remove');
        $this->filesystem->expects($this->never())
            ->method('rename');

        $this->assertFalse($this->adapter->makeActual($website, $version));
    }

    public function testMakeActualExistingVersion()
    {
        $website = $this->getConfiguredWebsite();
        $version = '2';
        $actualVersionPath = $this->getPath(1, SitemapFilesystemAdapter::ACTUAL_VERSION);
        $versionPath = $this->getPath(1, $version);

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($versionPath)
            ->willReturn(true);
        $this->assertMakeActualCalled($actualVersionPath, $versionPath, $version);

        $this->assertTrue($this->adapter->makeActual($website, $version));
    }

    public function testGetActualVersionNumberNoActualVersionExists()
    {
        $website = $this->getConfiguredWebsite();
        $actualVersionPath = $this->getPath(1, SitemapFilesystemAdapter::ACTUAL_VERSION);
        $versionFile = $actualVersionPath . DIRECTORY_SEPARATOR . SitemapFilesystemAdapter::VERSION_FILE_NAME;
        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($versionFile)
            ->willReturn(false);

        $this->assertSame(0, $this->adapter->getActualVersionNumber($website));
    }

    public function testGetActualVersionNumberActualVersionExists()
    {
        $website = $this->getConfiguredWebsite();
        $actualVersionPath = $this->getPath(1, SitemapFilesystemAdapter::ACTUAL_VERSION);
        $versionFile = $actualVersionPath . DIRECTORY_SEPARATOR . SitemapFilesystemAdapter::VERSION_FILE_NAME;
        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($versionFile)
            ->willReturn(true);

        $this->assertSame(2, $this->adapter->getActualVersionNumber($website));
    }

    public function testMakeNewerVersionActualHigherVersion()
    {
        $website = $this->getConfiguredWebsite();
        $version = '3';
        $actualVersionPath = $this->getPath(1, SitemapFilesystemAdapter::ACTUAL_VERSION);
        $versionPath = $this->getPath(1, $version);

        $versionFile = $actualVersionPath . DIRECTORY_SEPARATOR . SitemapFilesystemAdapter::VERSION_FILE_NAME;

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->withConsecutive(
                [$versionFile],
                [$versionPath]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );
        $this->assertMakeActualCalled($actualVersionPath, $versionPath, $version);

        $this->assertTrue($this->adapter->makeNewerVersionActual($website, $version));
    }

    public function testMakeNewerVersionActualVersionNotExists()
    {
        $website = $this->getConfiguredWebsite();
        $version = '3';
        $actualVersionPath = $this->getPath(1, SitemapFilesystemAdapter::ACTUAL_VERSION);
        $versionPath = $this->getPath(1, $version);

        $versionFile = $actualVersionPath . DIRECTORY_SEPARATOR . SitemapFilesystemAdapter::VERSION_FILE_NAME;

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->withConsecutive(
                [$versionFile],
                [$versionPath]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->filesystem->expects($this->never())
            ->method('remove');
        $this->filesystem->expects($this->never())
            ->method('rename');

        $this->assertFalse($this->adapter->makeNewerVersionActual($website, $version));
    }

    public function testMakeNewerVersionActualLowerVersion()
    {
        $website = $this->getConfiguredWebsite();
        $version = '1';
        $actualVersionPath = $this->getPath(1, SitemapFilesystemAdapter::ACTUAL_VERSION);

        $versionFile = $actualVersionPath . DIRECTORY_SEPARATOR . SitemapFilesystemAdapter::VERSION_FILE_NAME;

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($versionFile)
            ->willReturn(true);
        $this->filesystem->expects($this->never())
            ->method('remove');
        $this->filesystem->expects($this->never())
            ->method('rename');

        $this->assertFalse($this->adapter->makeNewerVersionActual($website, $version));
    }

    public function testGetSitemapFilesUnknownPath()
    {
        $website = $this->getConfiguredWebsite();
        $version = '100';
        $this->assertEmpty($this->adapter->getSitemapFiles($website, $version));
    }

    public function testGetSitemapFilesWithoutPattern()
    {
        $website = $this->getConfiguredWebsite();
        $version = 'actual';
        $filesIterator = $this->adapter->getSitemapFiles($website, $version);
        $this->assertInstanceOf(\Traversable::class, $filesIterator);
        $this->assertCount(3, $filesIterator);
        $actualFileNames = [];
        foreach ($filesIterator as $fileInfo) {
            $actualFileNames[] = $fileInfo->getFilename();
        }

        $expectedFiles = [
            'sitemap-page-1.xml',
            'sitemap-page-2.xml',
            'sitemap-product-1.xml',
        ];
        $this->assertEquals($expectedFiles, $actualFileNames);
    }

    public function testGetSitemapFilesWithPattern()
    {
        $website = $this->getConfiguredWebsite();
        $version = 'actual';
        $filesIterator = $this->adapter->getSitemapFiles($website, $version, 'sitemap-product-*.xml*');
        $this->assertInstanceOf(\Traversable::class, $filesIterator);
        $this->assertCount(1, $filesIterator);
        $actualFileNames = [];
        foreach ($filesIterator as $fileInfo) {
            $actualFileNames[] = $fileInfo->getFilename();
        }

        $expectedFiles = [
            'sitemap-product-1.xml',
        ];
        $this->assertEquals($expectedFiles, $actualFileNames);
    }

    /**
     * @return WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getConfiguredWebsite()
    {
        /** @var WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        return $website;
    }

    /**
     * @param int $websiteId
     * @param string $version
     * @return string
     */
    private function getPath($websiteId, $version)
    {
        return implode(DIRECTORY_SEPARATOR, [$this->path, $websiteId, $version]);
    }

    /**
     * @param string $actualVersionPath
     * @param string $versionPath
     * @param string $version
     */
    private function assertMakeActualCalled($actualVersionPath, $versionPath, $version)
    {
        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with($actualVersionPath);
        $this->filesystem->expects($this->once())
            ->method('rename')
            ->with($versionPath, $actualVersionPath);
        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with($actualVersionPath . DIRECTORY_SEPARATOR . SitemapFilesystemAdapter::VERSION_FILE_NAME, $version);
    }
}
