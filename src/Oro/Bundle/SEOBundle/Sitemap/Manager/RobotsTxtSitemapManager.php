<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Manager;

use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Component\Website\WebsiteInterface;

/**
 * Manages sitemap section of robots.txt file.
 */
class RobotsTxtSitemapManager
{
    private const KEYWORD_SITEMAP = 'Sitemap';

    private const AUTO_GENERATED_MARK = '# auto-generated';

    /** @var RobotsTxtFileManager */
    private $fileManager;

    /** @var array */
    private $existingSitemaps = [];

    /** @var array */
    private $newSitemaps = [];

    /** @var array */
    private $content = [];

    public function __construct(RobotsTxtFileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function flush(WebsiteInterface $website)
    {
        $this->ensureContentLoaded($website);

        // Add new sitemaps
        foreach ($this->newSitemaps as $sitemap) {
            if (in_array($sitemap, $this->existingSitemaps, true)) {
                continue;
            }
            $this->content[] = sprintf(
                '%s: %s %s',
                self::KEYWORD_SITEMAP,
                $sitemap,
                self::AUTO_GENERATED_MARK
            );
        }

        $this->fileManager->dumpContent(implode(PHP_EOL, $this->content), $website);
        $this->clear();
    }

    /**
     * @param string $sitemap
     */
    public function addSitemap($sitemap)
    {
        if (!in_array($sitemap, $this->newSitemaps, true)) {
            $this->newSitemaps[] = $sitemap;
        }
    }

    private function ensureContentLoaded(WebsiteInterface $website)
    {
        if (!$this->content) {
            $this->parseContent($this->fileManager->getContent($website));
        }
    }

    /**
     * @param string $content
     */
    private function parseContent($content)
    {
        $urlRegex = '((http[s]?|ftp):\/)?((\/?([^:\/\s]+)((\/\w+)*\/)([\w\-\.]+[^#?\s]+(.*)?(#[\w\-]+)?)))';
        $sitemapRegex = sprintf(
            '/^\s*%s\s*:\s*(%s)\s+%s\s*$/i',
            self::KEYWORD_SITEMAP,
            $urlRegex,
            self::AUTO_GENERATED_MARK
        );

        $this->content = explode(PHP_EOL, $content);
        foreach ($this->content as $line) {
            if (preg_match($sitemapRegex, $line, $matches) && !empty($matches[1])) {
                $this->existingSitemaps[] = $matches[1];
            }
        }
    }

    private function clear()
    {
        $this->existingSitemaps = [];
        $this->newSitemaps = [];
        $this->content = [];
    }
}
