<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Filesystem\Filesystem;

class SitemapFilesystemAdapter
{
    const ACTUAL_VERSION = 'actual';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var SitemapFileWriterInterface
     */
    private $fileWriter;

    /**
     * @var string
     */
    private $path;

    /**
     * @param Filesystem $filesystem
     * @param SitemapFileWriterInterface $fileWriter
     * @param string $path
     */
    public function __construct(
        Filesystem $filesystem,
        SitemapFileWriterInterface $fileWriter,
        $path
    ) {
        $this->filesystem = $filesystem;
        $this->fileWriter = $fileWriter;
        $this->path = $path;
    }

    /**
     * @param string $filename
     * @param WebsiteInterface $website
     * @param string $version
     * @param SitemapUrlsStorageInterface $sitemapUrlsStorage
     */
    public function dumpSitemapStorage(
        $filename,
        WebsiteInterface $website,
        $version,
        SitemapUrlsStorageInterface $sitemapUrlsStorage
    ) {
        $path = $this->getVersionedPath($website, $version);
        $this->filesystem->mkdir($path);

        $this->fileWriter->saveSitemap($sitemapUrlsStorage, $path . DIRECTORY_SEPARATOR . $filename);
    }

    /**
     * @param WebsiteInterface $website
     * @param string $version
     */
    public function makeActual(WebsiteInterface $website, $version)
    {
        $actualVersionPath = $this->getVersionedPath($website, self::ACTUAL_VERSION);
        $this->filesystem->remove($actualVersionPath);
        $this->filesystem->rename($this->getVersionedPath($website, $version), $actualVersionPath);
    }

    /**
     * @param WebsiteInterface $website
     * @param string $version
     * @param string|null $regex
     * @return \Iterator
     */
    public function getSitemapFiles(WebsiteInterface $website, $version, $regex = null)
    {
        $iterator = new \FilesystemIterator(
            $this->getVersionedPath($website, $version),
            \FilesystemIterator::SKIP_DOTS
        );

        if ($regex) {
            $iterator = new \RegexIterator($iterator, $regex);
        }

        return $iterator;
    }

    /**
     * @param WebsiteInterface $website
     * @param string $version
     * @return string
     */
    private function getVersionedPath(WebsiteInterface $website, $version)
    {
        return implode(DIRECTORY_SEPARATOR, [$this->path, $website->getId(), $version]);
    }
}
