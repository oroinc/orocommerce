<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Filesystem;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PublicSitemapFilesystemAdapterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileManager|MockObject */
    private $fileManager;

    /** @var FileManager|MockObject */
    private $tmpDataFileManager;

    /** @var RobotsTxtFileManager|MockObject */
    private $robotsTxtFileManager;

    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var PublicSitemapFilesystemAdapter */
    private $adapter;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->tmpDataFileManager = $this->createMock(FileManager::class);
        $this->robotsTxtFileManager = $this->createMock(RobotsTxtFileManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->adapter = new PublicSitemapFilesystemAdapter(
            $this->fileManager,
            $this->tmpDataFileManager,
            $this->robotsTxtFileManager,
            $this->doctrine
        );
        $this->adapter->setLogger($this->logger);
    }

    public function testMoveSitemaps()
    {
        $websiteIds = [1];
        $website = new Website();

        $this->fileManager->expects($this->once())
            ->method('deleteAllFiles');
        $this->tmpDataFileManager->expects($this->once())
            ->method('findFiles')
            ->with(1 . DIRECTORY_SEPARATOR)
            ->willReturn(['fileName1']);
        $this->tmpDataFileManager->expects($this->exactly(2))
            ->method('getFileContent')
            ->withConsecutive(
                ['fileName1'],
                ['robotsFileName.txt', false]
            )
            ->willReturnOnConsecutiveCalls(
                'content',
                'robots_content'
            );
        $this->tmpDataFileManager->expects($this->exactly(2))
            ->method('deleteFile')
            ->withConsecutive(
                ['fileName1'],
                ['robotsFileName.txt']
            );

        $this->fileManager->expects($this->exactly(2))
            ->method('writeToStorage')
            ->withConsecutive(
                ['content', 'fileName1'],
                ['robots_content', 'robotsFileName.txt']
            );

        $repo = $this->createMock(WebsiteRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($website);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($repo);

        $this->robotsTxtFileManager->expects($this->once())
            ->method('getFileNameByWebsite')
            ->with($website)
            ->willReturn('robotsFileName.txt');

        $this->adapter->moveSitemaps($websiteIds);
    }

    public function testMoveSitemapsWhenTempFileRemovalFails()
    {
        $websiteIds = [1];
        $website = new Website();

        $this->fileManager->expects($this->once())
            ->method('deleteAllFiles');
        $this->tmpDataFileManager->expects($this->once())
            ->method('findFiles')
            ->with(1 . DIRECTORY_SEPARATOR)
            ->willReturn(['fileName1']);
        $this->tmpDataFileManager->expects($this->exactly(2))
            ->method('getFileContent')
            ->withConsecutive(
                ['fileName1'],
                ['robotsFileName.txt', false]
            )
            ->willReturnOnConsecutiveCalls(
                'content',
                'robots_content'
            );

        $exception = new \Exception('Test');
        $this->tmpDataFileManager->expects($this->exactly(2))
            ->method('deleteFile')
            ->withConsecutive(
                ['fileName1'],
                ['robotsFileName.txt']
            )
            ->willThrowException($exception);

        $this->logger->expects($this->exactly(2))
            ->method('warning')
            ->withConsecutive(
                [
                    'Unexpected error occurred during temp file removal',
                    [
                        'fileName' => 'fileName1',
                        'exception' => $exception
                    ]
                ],
                [
                    'Unexpected error occurred during temp file removal',
                    [
                        'fileName' => 'robotsFileName.txt',
                        'exception' => $exception
                    ]
                ]
            );

        $this->fileManager->expects($this->exactly(2))
            ->method('writeToStorage')
            ->withConsecutive(
                ['content', 'fileName1'],
                ['robots_content', 'robotsFileName.txt']
            );

        $repo = $this->createMock(WebsiteRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($website);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($repo);

        $this->robotsTxtFileManager->expects($this->once())
            ->method('getFileNameByWebsite')
            ->with($website)
            ->willReturn('robotsFileName.txt');

        $this->adapter->moveSitemaps($websiteIds);
    }

    public function testClearTempStorage()
    {
        $this->tmpDataFileManager->expects($this->once())
            ->method('deleteAllFiles');

        $this->adapter->clearTempStorage();
    }

    public function testClearTempStorageFails()
    {
        $exception = new \Exception('Test');
        $this->tmpDataFileManager->expects($this->once())
            ->method('deleteAllFiles')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Unexpected error occurred during temp storage clearing',
                [
                    'exception' => $exception
                ]
            );

        $this->adapter->clearTempStorage();
    }
}
