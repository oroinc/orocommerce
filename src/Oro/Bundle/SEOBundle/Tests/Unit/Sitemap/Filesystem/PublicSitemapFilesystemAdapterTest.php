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

    /** @var PublicSitemapFilesystemAdapter */
    private $adapter;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    #[\Override]
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

    /**
     * @dataProvider tokenDataProvider
     */
    public function testMoveSitemaps(int $expectedDeleteCall, ?OrganizationAwareTokenInterface $token)
    {
        $websiteIds = [1, 2];
        $website1 = new Website();
        $website2 = new Website();

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->fileManager->expects($this->exactly($expectedDeleteCall))
            ->method('deleteAllFiles');
        $this->tmpDataFileManager->expects($this->exactly(2))
            ->method('findFiles')
            ->withConsecutive(
                [1 . DIRECTORY_SEPARATOR],
                [2 . DIRECTORY_SEPARATOR]
            )
            ->willReturnOnConsecutiveCalls(
                ['fileName1'],
                ['fileName2']
            );

        $this->tmpDataFileManager->expects($this->exactly(4))
            ->method('getFileContent')
            ->withConsecutive(
                ['fileName1'],
                ['robotsFileName1.txt', false],
                ['fileName2'],
                ['robotsFileName2.txt', false]
            )
            ->willReturnOnConsecutiveCalls(
                'content_1',
                'robots_content_1',
                'content_2',
                'robots_content_2'
            );
        $this->tmpDataFileManager->expects($this->exactly(4))
            ->method('deleteFile')
            ->withConsecutive(
                ['fileName1'],
                ['robotsFileName1.txt'],
                ['fileName2'],
                ['robotsFileName2.txt']
            );

        $this->fileManager->expects($this->exactly(4))
            ->method('writeToStorage')
            ->withConsecutive(
                ['content_1', 'fileName1'],
                ['robots_content_1', 'robotsFileName1.txt'],
                ['content_2', 'fileName2'],
                ['robots_content_2', 'robotsFileName2.txt']
            );

        $repo = $this->createMock(WebsiteRepository::class);
        $repo->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls(
                $website1,
                $website2
            );

        $this->doctrine->expects($this->exactly(2))
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($repo);

        $this->robotsTxtFileManager->expects($this->exactly(2))
            ->method('getFileNameByWebsite')
            ->withConsecutive(
                [$website1],
                [$website2],
            )
            ->willReturnOnConsecutiveCalls(
                'robotsFileName1.txt',
                'robotsFileName2.txt',
            );

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

    public function tokenDataProvider(): array
    {
        $token = $this->createMock(OrganizationAwareTokenInterface::class);

        return [
            [2, $token],
            [1, null],
        ];
    }
}
