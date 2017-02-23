<?php

namespace Oro\Bundle\SEOBundle\Tools;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class SitemapFileWriterFactory
{
    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileSystem;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    public function __construct(Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->fileSystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * @return SitemapFileWriter
     */
    public function create()
    {
        $sitemapFileWriter = new SitemapFileWriter($this->fileSystem, $this->logger);

        if (extension_loaded('zip')) {
            $sitemapFileWriter->setZipArchive(new \ZipArchive());
        }

        return $sitemapFileWriter;
    }
}
