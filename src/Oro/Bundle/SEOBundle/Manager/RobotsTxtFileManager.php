<?php

namespace Oro\Bundle\SEOBundle\Manager;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Exception\RobotsTxtFileManagerException;
use Oro\Component\Website\WebsiteInterface;
use Psr\Log\LoggerInterface;

/**
 * The manager that simplifies work with robots.txt files.
 */
class RobotsTxtFileManager
{
    /** @var FileManager */
    private $fileManager;

    /** @var CanonicalUrlGenerator */
    private $urlGenerator;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param FileManager           $fileManager
     * @param CanonicalUrlGenerator $urlGenerator
     * @param LoggerInterface       $logger
     */
    public function __construct(
        FileManager $fileManager,
        CanonicalUrlGenerator $urlGenerator,
        LoggerInterface $logger
    ) {
        $this->fileManager = $fileManager;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return bool
     */
    public function isContentFileExist(WebsiteInterface $website): bool
    {
        return $this->fileManager->hasFile($this->getFileNameByWebsite($website));
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return string|null
     *
     * @throws RobotsTxtFileManagerException
     */
    public function getContent(WebsiteInterface $website): ?string
    {
        $filePath = $this->getFileNameByWebsite($website);
        $content = $this->fileManager->getFileContent($filePath, false);
        if (null === $content) {
            $message = sprintf('An error occurred while reading robots.txt file from %s', $filePath);
            $this->logger->error($message);

            throw new RobotsTxtFileManagerException($message);
        }

        return $content;
    }

    /**
     * Dumps content of robots.txt file to robots.{website_host}.txt file.
     *
     * @param string           $content
     * @param WebsiteInterface $website
     *
     * @throws RobotsTxtFileManagerException
     */
    public function dumpContent(string $content, WebsiteInterface $website): void
    {
        $this->dumpToFile($this->getFileNameByWebsite($website), $content);
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return string
     */
    public function getFileNameByWebsite(WebsiteInterface $website): string
    {
        return 'robots.' . $this->getWebsiteHost($website) . '.txt';
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return string
     */
    private function getWebsiteHost(WebsiteInterface $website): string
    {
        $websiteUrl = $this->urlGenerator->getCanonicalDomainUrl($website);
        $urlParts = parse_url($websiteUrl);

        return $urlParts['host'];
    }

    /**
     * @param string $filePath
     * @param string $content
     *
     * @throws RobotsTxtFileManagerException
     */
    private function dumpToFile(string $filePath, string $content): void
    {
        try {
            $this->fileManager->writeToStorage($content, $filePath);
        } catch (\Exception $e) {
            $message = sprintf('An error occurred while writing robots.txt file to %s', $filePath);
            $this->logger->error($message);

            throw new RobotsTxtFileManagerException(
                $message,
                $e->getCode(),
                $e
            );
        }
    }
}
