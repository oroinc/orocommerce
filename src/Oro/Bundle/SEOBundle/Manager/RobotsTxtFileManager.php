<?php

namespace Oro\Bundle\SEOBundle\Manager;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Exception\RobotsTxtFileManagerException;
use Oro\Component\Website\WebsiteInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The manager that simplifies work with robots txt files.
 */
class RobotsTxtFileManager
{
    /** @deprecated  */
    const ROBOTS_TXT_FILENAME = 'robots.txt';

    const AUTO_GENERATED_MARK = '# auto-generated';

    /** @var LoggerInterface */
    private $logger;

    /** @var Filesystem */
    private $filesystem;

    /** @var CanonicalUrlGenerator */
    private $urlGenerator;

    /** @var string */
    private $defaultFilePath;

    /** @var string */
    private $path;

    /**
     * @param LoggerInterface       $logger
     * @param Filesystem            $filesystem
     * @param CanonicalUrlGenerator $urlGenerator
     * @param string                $defaultFilePath
     * @param string                $path
     */
    public function __construct(LoggerInterface $logger, Filesystem $filesystem, $path)
    {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->path = $path;
    }

    /**
     * @param string $defaultFilePath
     */
    public function setDefaultRobotsPath(string $defaultFilePath)
    {
        $this->defaultFilePath = $defaultFilePath;
    }

    /**
     * @param CanonicalUrlGenerator $urlGenerator
     */
    public function setUrlGenerator(CanonicalUrlGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return bool
     */
    public function isContentFileExist(WebsiteInterface $website): bool
    {
        $fileName = $this->getFileNameByWebsite($website);
        $filePath = $this->getFullName($fileName);

        return file_exists($filePath);
    }

    /**
     * @return false|string
     * @throws RobotsTxtFileManagerException
     */
    public function getContent()
    {
        $content = @file_get_contents($this->getDefaultRobotsFileFullName());
        if ($content === false) {
            $message = sprintf(
                'An error occurred while reading robots.txt file from %s',
                $this->getDefaultRobotsFileFullName()
            );
            $this->logger->error($message);

            throw new RobotsTxtFileManagerException($message);
        }

        return $content;
    }

    /**
     * @deprecated At the next versions the 'getContent' method will have $website parameter.
     * @param WebsiteInterface $website
     *
     * @return false|string
     * @throws RobotsTxtFileManagerException
     */
    public function getContentForWebsite(WebsiteInterface $website)
    {
        $fileName = $this->getFileNameByWebsite($website);
        $filePath = $this->getFullName($fileName);
        $content = @file_get_contents($filePath);
        if ($content === false) {
            $message = sprintf('An error occurred while reading robots file from %s', $filePath);
            $this->logger->error($message);

            throw new RobotsTxtFileManagerException($message);
        }

        return $content;
    }

    /**
     * @param $content
     *
     * @throws RobotsTxtFileManagerException
     */
    public function dumpContent($content)
    {
        $fileName = $this->getDefaultRobotsFileFullName();
        $this->dumpToFile($fileName, $content);
    }

    /**
     * @deprecated At the next versions the 'dumpContent' method will have $website parameter.
     * Dumps content of robots txt file to $path/appropriate_website_domain.txt file
     *
     * @param                  $content
     * @param WebsiteInterface $website
     *
     * @throws RobotsTxtFileManagerException
     */
    public function dumpContentForWebsite($content, WebsiteInterface $website)
    {
        $this->dumpToFile($this->getFullName($this->getFileNameByWebsite($website)), $content);
        if ($this->isDefaultRobotsFileShouldBeDumped($website)) {
            $this->dumpToFile($this->getDefaultRobotsFileFullName(), $content);
        }
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return string
     */
    public function getFileNameByWebsite(WebsiteInterface $website): string
    {
        $websiteUlr = $this->urlGenerator->getCanonicalDomainUrl($website);
        $urlParts = parse_url($websiteUlr);

        return 'robots.' . $urlParts['host'].'.txt';
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return bool
     */
    private function isDefaultRobotsFileShouldBeDumped(WebsiteInterface $website): bool
    {
        if (null === $website || !$website->isDefault()) {
            return false;
        }

        $fullFilePath = $this->getDefaultRobotsFileFullName();

        if (is_file($fullFilePath)) {
            return is_writable($fullFilePath);
        }

        return is_writable(dirname($fullFilePath));
    }

    /**
     * @param string $filePath
     * @param        $content
     *
     * @throws RobotsTxtFileManagerException
     */
    private function dumpToFile(string $filePath, $content): void
    {
        try {
            $this->filesystem->dumpFile($filePath, $content);
        } catch (IOExceptionInterface $e) {
            $message = sprintf('An error occurred while writing robots file to %s', $filePath);
            $this->logger->error($message);

            throw new RobotsTxtFileManagerException($message);
        }
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getFullName($fileName)
    {
        return implode(DIRECTORY_SEPARATOR, [$this->path, $fileName]);
    }

    /**
     * @return string
     */
    private function getDefaultRobotsFileFullName(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->defaultFilePath, self::ROBOTS_TXT_FILENAME]);
    }
}
