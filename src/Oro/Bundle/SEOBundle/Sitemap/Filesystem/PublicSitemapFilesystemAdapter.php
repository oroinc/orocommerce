<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides functionality to move sitemap related files from a temporary private storage to a public storage.
 */
class PublicSitemapFilesystemAdapter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private FileManager $fileManager,
        private FileManager $tmpDataFileManager,
        private RobotsTxtFileManager $robotsTxtFileManager,
        private ManagerRegistry $doctrine,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * Moves website sitemaps and robots.txt file from the temporary filesystem storage to the Gaufrette storage.
     *
     * @param int[] $websiteIds [websiteId, ...]
     */
    public function moveSitemaps(array $websiteIds): void
    {
        try {
            $isInOrganization = $this->isInOrganization();
            if (!$isInOrganization) {
                $this->fileManager->deleteAllFiles();
            }

            foreach ($websiteIds as $websiteId) {
                if ($isInOrganization) {
                    $this->fileManager->deleteAllFiles($websiteId);
                }

                $this->moveSitemapFiles($websiteId);
                $this->moveRobotsTxtFile($websiteId);
            }
        } finally {
            $this->clearTempStorage();
        }
    }

    /**
     * Deletes all files form the temporary filesystem storage.
     */
    public function clearTempStorage(): void
    {
        try {
            $this->tmpDataFileManager->deleteAllFiles();
        } catch (\Exception $e) {
            // Tmp file removal should not interrupt move process.
            if ($this->logger) {
                $this->logger->warning(
                    'Unexpected error occurred during temp storage clearing',
                    [
                        'exception' => $e
                    ]
                );
            }
        }
    }

    private function moveSitemapFiles(int $websiteId): void
    {
        $fileNames = $this->tmpDataFileManager->findFiles($websiteId . DIRECTORY_SEPARATOR);
        foreach ($fileNames as $fileName) {
            $this->fileManager->writeToStorage($this->tmpDataFileManager->getFileContent($fileName), $fileName);
            $this->removeTmpFile($fileName);
        }
    }

    private function moveRobotsTxtFile(int $websiteId): void
    {
        $fileName = $this->robotsTxtFileManager->getFileNameByWebsite(
            $this->doctrine->getRepository(Website::class)->find($websiteId)
        );
        $fileContent = $this->tmpDataFileManager->getFileContent($fileName, false);
        if (null !== $fileContent) {
            $this->fileManager->writeToStorage($fileContent, $fileName);
            $this->removeTmpFile($fileName);
        }
    }

    private function removeTmpFile(string $fileName): void
    {
        try {
            $this->tmpDataFileManager->deleteFile($fileName);
        } catch (\Exception $e) {
            // Tmp file removal should not interrupt move process.
            if ($this->logger) {
                $this->logger->warning(
                    'Unexpected error occurred during temp file removal',
                    [
                        'fileName' => $fileName,
                        'exception' => $e
                    ]
                );
            }
        }
    }

    private function isInOrganization(): bool
    {
        return $this->tokenStorage->getToken() instanceof OrganizationAwareTokenInterface;
    }
}
