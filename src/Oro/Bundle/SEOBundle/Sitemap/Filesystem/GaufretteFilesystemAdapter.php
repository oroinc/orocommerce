<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Website\WebsiteInterface;

/**
 * Provides functionality to move sitemap related files from a temporary private storage to a public storage.
 */
class GaufretteFilesystemAdapter
{
    /** @var FileManager */
    private $fileManager;

    /** @var RobotsTxtFileManager */
    private $robotsTxtFileManager;

    /** @var string */
    private $tempDirectory;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var FileManager */
    private $tmpDataFileManager;

    /**
     * @param FileManager          $fileManager
     * @param RobotsTxtFileManager $robotsTxtFileManager
     * @param ManagerRegistry      $doctrine
     * @param string               $tempDirectory
     */
    public function __construct(
        FileManager $fileManager,
        RobotsTxtFileManager $robotsTxtFileManager,
        ManagerRegistry $doctrine,
        string $tempDirectory
    ) {
        $this->fileManager = $fileManager;
        $this->robotsTxtFileManager = $robotsTxtFileManager;
        $this->doctrine = $doctrine;
        $this->tempDirectory = $tempDirectory;
    }

    /**
     * @param FileManager $tmpDataFileManager
     */
    public function setTmpDataFileManager(FileManager $tmpDataFileManager)
    {
        $this->tmpDataFileManager = $tmpDataFileManager;
    }

    /**
     * Moves website sitemaps and robots txt file from the temporary filesystem storage to the Gaufrette storage.
     *
     * @param array $websiteIds [websiteId, ...]
     */
    public function moveSitemaps(array $websiteIds): void
    {
        try {
            $this->fileManager->deleteAllFiles();
            foreach ($websiteIds as $websiteId) {
                $this->moveSitemapFiles($websiteId);
                $this->moveRobotsFile($this->doctrine->getRepository(Website::class)->find($websiteId));
            }
        } finally {
            $this->clearTempStorage();
        }
    }

    /**
     * Clears the temporary filesystem storage
     */
    public function clearTempStorage(): void
    {
        $this->tmpDataFileManager->deleteAllFiles();
        if (is_dir($this->tempDirectory)) {
            $this->removeFilesystemDirectory($this->tempDirectory);
        }
    }

    /**
     * @param WebsiteInterface $website
     */
    private function moveRobotsFile(WebsiteInterface $website): void
    {
        $fileName = $this->robotsTxtFileManager->getFileNameByWebsite($website);
        $fileContent = $this->tmpDataFileManager->getFileContent($fileName, false);
        if (null !== $fileContent) {
            $this->fileManager->writeToStorage($fileContent, $fileName);
            $this->tmpDataFileManager->deleteFile($fileName);
        }
    }

    /**
     * @param int $websiteId
     */
    private function moveSitemapFiles(int $websiteId): void
    {
        $fileNames = $this->tmpDataFileManager->findFiles($websiteId . DIRECTORY_SEPARATOR);
        foreach ($fileNames as $fileName) {
            $this->fileManager->writeToStorage($this->tmpDataFileManager->getFileContent($fileName), $fileName);
            $this->tmpDataFileManager->deleteFile($fileName);
        }
    }

    /**
     * @param string $dir
     */
    private function removeFilesystemDirectory(string $dir): void
    {
        foreach (glob($dir) as $file) {
            if (is_dir($file)) {
                $this->removeFilesystemDirectory($file . '/*');
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }
}
