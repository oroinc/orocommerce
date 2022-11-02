<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Gaufrette\File;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;
use Oro\Component\Website\WebsiteInterface;

/**
 * Provides functionality to work with sitemap related files in a temporary private storage
 * that is used during sitemap generation.
 */
class SitemapFilesystemAdapter
{
    private const VERSION_FILE_NAME = 'version';

    /** @var FileManager */
    private $fileManager;

    /** @var SitemapFileWriterInterface */
    private $fileWriter;

    public function __construct(FileManager $fileManager, SitemapFileWriterInterface $fileWriter)
    {
        $this->fileManager = $fileManager;
        $this->fileWriter = $fileWriter;
    }

    /**
     * @throws SitemapFileWriterException
     */
    public function dumpSitemapStorage(
        string $filename,
        WebsiteInterface $website,
        SitemapStorageInterface $sitemapUrlsStorage
    ): void {
        if ($sitemapUrlsStorage->getUrlItemsCount() > 0) {
            $this->fileWriter->saveSitemap(
                $sitemapUrlsStorage->getContents(),
                $website->getId() . DIRECTORY_SEPARATOR . $filename
            );
        }
    }

    public function dumpVersion(WebsiteInterface $website, string $version): void
    {
        $this->fileManager->writeToStorage(
            $version,
            $website->getId() . DIRECTORY_SEPARATOR . self::VERSION_FILE_NAME
        );
    }

    /**
     * @param WebsiteInterface $website
     * @param string|null      $pattern
     * @param string|null      $notPattern
     *
     * @return File[]
     */
    public function getSitemapFiles(
        WebsiteInterface $website,
        string $pattern = null,
        string $notPattern = null
    ): array {
        $iterator = new FilenameFilterIterator(
            new \ArrayIterator(
                $this->fileManager->findFiles($website->getId() . DIRECTORY_SEPARATOR)
            ),
            $pattern ? [$pattern] : [],
            $notPattern ? [self::VERSION_FILE_NAME, $notPattern] : [self::VERSION_FILE_NAME]
        );

        return array_map(
            function (string $fileName) {
                return $this->fileManager->getFile($fileName);
            },
            iterator_to_array($iterator)
        );
    }
}
