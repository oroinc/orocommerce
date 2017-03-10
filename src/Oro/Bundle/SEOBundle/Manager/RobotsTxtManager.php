<?php

namespace Oro\Bundle\SEOBundle\Manager;

use Oro\Bundle\SEOBundle\Exception\RobotsTxtManagerException;
use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class RobotsTxtManager
{
    const ROBOTS_TXT_FILENAME = 'robots.txt';
    const KEYWORD_USER_AGENT = 'User-agent';
    const KEYWORD_DISALLOW = 'Disallow';
    const KEYWORD_ALLOW = 'Allow';
    const KEYWORD_SITEMAP = 'Sitemap';

    private static $availableKeywords = [
        self::KEYWORD_USER_AGENT,
        self::KEYWORD_DISALLOW,
        self::KEYWORD_ALLOW,
        self::KEYWORD_SITEMAP,
    ];

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
     * @param string $keyword
     * @param string $value
     * @throws InvalidArgumentException
     */
    public function addKeyword($keyword, $value)
    {
        $this->checkIsKeywordSupported($keyword);

        $content = '';
        if ($this->filesystem->exists($this->getFullName())) {
            $content = file_get_contents($this->getFullName());
        }

        if ($content) {
            $content .= PHP_EOL;
        }

        $content .= sprintf('%s: %s', $keyword, $value);
        $this->dumpFile($this->getFullName(), $content);
    }

    /**
     * @param $keyword
     * @throws InvalidArgumentException
     */
    public function removeByKeyword($keyword)
    {
        $this->checkIsKeywordSupported($keyword);

        if ($this->filesystem->exists($this->getFullName())) {
            $content = trim(preg_replace(
                sprintf('/%s*%s:.*/', PHP_EOL, $keyword),
                '',
                file_get_contents($this->getFullName())
            ));
            $this->dumpFile($this->getFullName(), $content);
        }
    }

    /**
     * @param string $keyword
     * @param string $value
     */
    public function changeByKeyword($keyword, $value)
    {
        $this->checkIsKeywordSupported($keyword);

        $content = '';
        if ($this->filesystem->exists($this->getFullName())) {
            $content = trim(preg_replace(
                sprintf('/%s*%s:.*/', PHP_EOL, $keyword),
                '',
                file_get_contents($this->getFullName())
            ));
        }

        if ($content) {
            $content .= PHP_EOL;
        }

        $content .= sprintf('%s: %s', $keyword, $value);
        $this->dumpFile($this->getFullName(), $content);
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function isSupportedKeyword($keyword)
    {
        return in_array($keyword, self::getAvailableKeywords(), true);
    }

    /**
     * @return array
     */
    public static function getAvailableKeywords()
    {
        return self::$availableKeywords;
    }

    /**
     * @param string $keyword
     * @throws InvalidArgumentException
     */
    private function checkIsKeywordSupported($keyword)
    {
        if (!$this->isSupportedKeyword($keyword)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported keyword: %s, supported keywords are: %s',
                $keyword,
                implode(', ', self::getAvailableKeywords())
            ));
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

    /**
     * @param string $path
     * @param string $content
     * @throws RobotsTxtManagerException
     */
    private function dumpFile($path, $content)
    {
        try {
            $this->filesystem->dumpFile($path, $content);
        } catch (IOExceptionInterface $e) {
            $message = sprintf('An error occurred while writing robots.txt file to %s', $path);
            $this->logger->error($message);

            throw new RobotsTxtManagerException($message);
        }
    }
}
