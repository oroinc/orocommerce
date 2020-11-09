<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The main implementation of writing sitemap related data to files.
 */
class SitemapFileWriter implements SitemapFileWriterInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var FileManager */
    private $fileManager;

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
     * @param FileManager $fileManager
     */
    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @param string $sitemapContents
     * @param string $path
     * @return string $path
     * @throws SitemapFileWriterException
     */
    public function saveSitemap($sitemapContents, $path)
    {
        try {
            $this->fileManager->writeToStorage($sitemapContents, $path);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());

            throw new SitemapFileWriterException(
                sprintf('An error occurred while writing sitemap to %s', $path),
                $e->getCode(),
                $e
            );
        }

        return $path;
    }
}
