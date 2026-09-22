<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Filesystem;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

    /** @var TokenStorageInterface|MockObject */
    private $tokenStorage;

    /** @var PublicSitemapFilesystemAdapter */
    private $adapter;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->tmpDataFileManager = $this->createMock(FileManager::class);
        $this->robotsTxtFileManager = $this->createMock(RobotsTxtFileManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->adapter = new PublicSitemapFilesystemAdapter(
            $this->fileManager,
            $this->tmpDataFileManager,
            $this->robotsTxtFileManager,
            $this->doctrine
        );
        $this->adapter->setLogger($this->logger);
        $this->adapter->setTokenStorage($this->tokenStorage);
    }

    public function testMoveSitemaps()
    {
        $websiteIds = [1];
        $website = new Website();

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);
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

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);
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

    public function testMoveSitemapsWhenInOrganization()
    {
        $websiteIds = [1, 2];
        $website1 = new Website();
        $website2 = new Website();

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(OrganizationAwareTokenInterface::class));

        $this->fileManager->expects($this->exactly(2))
            ->method('deleteAllFiles')
            ->withConsecutive([1], [2]);

        $this->tmpDataFileManager->expects($this->exactly(2))
            ->method('findFiles')
            ->withConsecutive([1 . DIRECTORY_SEPARATOR], [2 . DIRECTORY_SEPARATOR])
            ->willReturn([]);

        $this->tmpDataFileManager->expects($this->exactly(2))
            ->method('getFileContent')
            ->withConsecutive(['robotsFileName1.txt', false], ['robotsFileName2.txt', false])
            ->willReturn(null);

        $repo = $this->createMock(WebsiteRepository::class);
        $repo->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($website1, $website2);
        $this->doctrine->expects($this->exactly(2))
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($repo);

        $this->robotsTxtFileManager->expects($this->exactly(2))
            ->method('getFileNameByWebsite')
            ->withConsecutive([$website1], [$website2])
            ->willReturnOnConsecutiveCalls('robotsFileName1.txt', 'robotsFileName2.txt');

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
