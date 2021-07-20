<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;
use Psr\Log\LoggerInterface;

/**
 * The main implementation of writing sitemap related data to files.
 */
class SitemapFileWriter implements SitemapFileWriterInterface
{
    /** @var FileManager */
    private $fileManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(FileManager $fileManager, LoggerInterface $logger)
    {
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function saveSitemap(string $content, string $path): string
    {
        try {
            $this->fileManager->writeToStorage($content, $path);
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
