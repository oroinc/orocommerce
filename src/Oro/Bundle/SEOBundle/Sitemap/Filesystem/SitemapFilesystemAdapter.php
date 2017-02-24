<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageInterface;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SitemapFilesystemAdapter
{
    const ACTUAL_VERSION = 'actual';
    const VERSION_FILE_NAME = 'version';

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
     * @param SitemapStorageInterface $sitemapUrlsStorage
     */
    public function dumpSitemapStorage(
        $filename,
        WebsiteInterface $website,
        $version,
        SitemapStorageInterface $sitemapUrlsStorage
    ) {
        $path = $this->getVersionedPath($website, $version);
        $this->filesystem->mkdir($path);

        $this->fileWriter->saveSitemap($sitemapUrlsStorage->getContents(), $path . DIRECTORY_SEPARATOR . $filename);
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
        $this->filesystem->dumpFile($actualVersionPath . DIRECTORY_SEPARATOR . self::VERSION_FILE_NAME, $version);
    }

    /**
     * @param WebsiteInterface $website
     * @param string $version
     * @return bool
     */
    public function makeNewerVersionActual(WebsiteInterface $website, $version)
    {
        if ($version > $this->getActualVersionNumber($website)) {
            $this->makeActual($website, $version);

            return true;
        }

        return false;
    }

    /**
     * @param WebsiteInterface $website
     * @return int
     */
    public function getActualVersionNumber(WebsiteInterface $website)
    {
        $actualVersionPath = $this->getVersionedPath($website, self::ACTUAL_VERSION);
        $versionFilePath = $actualVersionPath . DIRECTORY_SEPARATOR . self::VERSION_FILE_NAME;
        if ($this->filesystem->exists($versionFilePath)) {
            return (int)file_get_contents($versionFilePath);
        }

        return 0;
    }

    /**
     * @param WebsiteInterface $website
     * @param string $version
     * @param string|null $pattern
     * @return \Traversable|null
     */
    public function getSitemapFiles(WebsiteInterface $website, $version, $pattern = null)
    {
        $path = $this->getVersionedPath($website, $version);
        if (!is_readable($path)) {
            return null;
        }

        $finder = Finder::create();
        $files = $finder->files()
            ->in($path)
            ->notName(self::VERSION_FILE_NAME);

        if ($pattern) {
            $files->name($pattern);
        }

        return $files;
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
