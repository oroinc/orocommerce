<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Finder\Finder;

/**
 * Adapter that allows to move website sitemaps and robots txt file
 * from the temporary filesystem storage to the Gaufrette storage.
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
     * Moves website sitemaps and robots txt file from the temporary filesystem storage to the Gaufrette storage.
     *
     * @param array $websiteIds [websiteId, ...]
     */
    public function moveSitemaps(array $websiteIds): void
    {
        try {
            $this->clearGaufretteStorage();
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
        $robotsPath = $this->tempDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($robotsPath)) {
            $this->fileManager->writeFileToStorage($robotsPath, $fileName);
            unlink($robotsPath);
        }
    }

    /**
     * @param int $websiteId
     */
    private function moveSitemapFiles(int $websiteId): void
    {
        $tempDirectory = $this->tempDirectory . DIRECTORY_SEPARATOR . $websiteId;
        $finder = Finder::create();
        $files = $finder->files()
            ->in($tempDirectory);

        foreach ($files as $file) {
            $filePath = $websiteId . DIRECTORY_SEPARATOR . $file->getRelativePathname();
            $this->fileManager->writeFileToStorage($file->getPathname(), $filePath);
            unlink($file->getPathname());
        }
    }

    private function clearGaufretteStorage(): void
    {
        $files = $this->fileManager->findFiles();
        foreach ($files as $file) {
            $this->fileManager->deleteFile($file);
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
