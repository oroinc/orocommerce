<?php

namespace Oro\Bundle\SEOBundle\Manager;

use Oro\Bundle\SEOBundle\Exception\RobotsTxtFileManagerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class RobotsTxtFileManager
{
    const ROBOTS_TXT_FILENAME = 'robots.txt';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $fullName;

    /**
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param string $path
     */
    public function __construct(LoggerInterface $logger, Filesystem $filesystem, $path)
    {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->path = $path;
    }

    /**
     * @return string
     * @throws RobotsTxtFileManagerException
     */
    public function getContent()
    {
        $content = @file_get_contents($this->getFullName());
        if ($content === false) {
            $message = sprintf('An error occurred while reading robots.txt file from %s', $this->getFullName());
            $this->logger->error($message);

            throw new RobotsTxtFileManagerException($message);
        }

        return $content;
    }

    /**
     * @param string $content
     * @throws RobotsTxtFileManagerException
     */
    public function dumpContent($content)
    {
        try {
            $this->filesystem->dumpFile($this->getFullName(), $content);
        } catch (IOExceptionInterface $e) {
            $message = sprintf('An error occurred while writing robots.txt file to %s', $this->getFullName());
            $this->logger->error($message);

            throw new RobotsTxtFileManagerException($message);
        }
    }

    /**
     * @return string
     */
    private function getFullName()
    {
        if (!$this->fullName) {
            $this->fullName = implode(DIRECTORY_SEPARATOR, [$this->path, self::ROBOTS_TXT_FILENAME]);
        }

        return $this->fullName;
    }
}
