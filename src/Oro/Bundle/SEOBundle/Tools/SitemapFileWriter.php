<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Oro\Bundle\SEOBundle\Tools\Exception\SitemapFileWriterException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class SitemapFileWriter
{
    const ARCHIVE_EXTENSION = 'zip';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \ZipArchive
     */
    private $zipArchive;

    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * @param \ZipArchive|null $zipArchive
     * @return $this
     */
    public function setZipArchive($zipArchive = null)
    {
        $this->zipArchive = $zipArchive;

        return $this;
    }

    /**
     * @return \ZipArchive|null $zipArchive
     */
    public function getZipArchive()
    {
        return $this->zipArchive;
    }

    /**
     * @param SitemapUrlsStorageInterface $sitemapStorage
     * @param string $path
     * @return string $path
     * @throws SitemapFileWriterException
     */
    public function saveSitemap(SitemapUrlsStorageInterface $sitemapStorage, $path)
    {
        if ($this->zipArchive) {
            return $this->saveAsZipFile($sitemapStorage, $path);
        } else {
            return $this->saveAsXml($sitemapStorage, $path);
        }
    }

    /**
     * @param SitemapUrlsStorageInterface $sitemapStorage
     * @param $path
     * @return string
     * @throws SitemapFileWriterException
     */
    private function saveAsZipFile(SitemapUrlsStorageInterface $sitemapStorage, $path)
    {
        $zipFilePath = sprintf('%s.%s', $path, self::ARCHIVE_EXTENSION) ;

        if (!$this->zipArchive->open($zipFilePath, \ZipArchive::CREATE)) {
            throw new SitemapFileWriterException(sprintf('Cannot open archive for sitemap %s', $zipFilePath));
        }

        $parts = explode('/', $path);
        $fileName = end($parts);
        if (!$this->zipArchive->addFromString($fileName, $sitemapStorage->getContents())) {
            throw new SitemapFileWriterException(sprintf('Cannot add data to archive for sitemap %s', $zipFilePath));
        }

        if (!$this->zipArchive->close()) {
            throw new SitemapFileWriterException(sprintf('Cannot save archive for sitemap %s', $zipFilePath));
        }

        return $zipFilePath;
    }

    /**
     * @param SitemapUrlsStorageInterface $sitemapStorage
     * @param $path
     * @return string
     * @throws SitemapFileWriterException
     */
    private function saveAsXml(SitemapUrlsStorageInterface $sitemapStorage, $path)
    {
        try {
            $this->filesystem->dumpFile($path, $sitemapStorage->getContents(), 0755);
        } catch (IOExceptionInterface $e) {
            $this->logger->debug($e->getMessage());

            throw new SitemapFileWriterException(sprintf('An error occurred while writing sitemap to %s', $path));
        }

        return $path;
    }
}
