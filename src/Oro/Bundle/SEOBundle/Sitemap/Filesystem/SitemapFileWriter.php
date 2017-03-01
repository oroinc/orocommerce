<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Filesystem;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Oro\Component\SEO\Tools\Exception\SitemapFileWriterException;

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
     * @param string $sitemapContents
     * @param string $path
     * @return string $path
     * @throws SitemapFileWriterException
     */
    public function saveSitemap($sitemapContents, $path)
    {
        try {
            $this->filesystem->dumpFile($path, $sitemapContents);
        } catch (IOExceptionInterface $e) {
            $this->logger->debug($e->getMessage());

            throw new SitemapFileWriterException(sprintf('An error occurred while writing sitemap to %s', $path));
        }

        return $path;
    }
}
