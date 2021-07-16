<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;

/**
 * Dumps the robots.txt file based on a template file if a robots.txt file was not dumped yet.
 */
class CopyRobotsTxtTemplateListener
{
    private const DEFAULT_TEMPLATE_FILE_NAME = 'robots.txt.dist';

    /** @var RobotsTxtFileManager */
    private $robotsTxtFileManager;

    /** @var string */
    private $robotsTxtPathDirectory;

    public function __construct(
        RobotsTxtFileManager $robotsTxtFileManager,
        string $robotsTxtPathDirectory
    ) {
        $this->robotsTxtFileManager = $robotsTxtFileManager;
        $this->robotsTxtPathDirectory = $robotsTxtPathDirectory;
    }

    public function onSitemapDumpStorage(OnSitemapDumpFinishEvent $event): void
    {
        $website = $event->getWebsite();

        if ($this->robotsTxtFileManager->isContentFileExist($website)) {
            // We should not rewrite already dumped file because another website have a similar domain
            // and the template was already dumped.
            return;
        }

        $content = $this->getTemplateContent($this->robotsTxtFileManager->getFileNameByWebsite($website));
        $this->robotsTxtFileManager->dumpContent($content, $website);
    }

    private function getTemplateContent(string $domainFileName): string
    {
        $websiteTemplateFileName = $this->robotsTxtPathDirectory . $domainFileName . '.dist';
        if (is_file($websiteTemplateFileName)) {
            return file_get_contents($websiteTemplateFileName);
        }

        $defaultTemplateFileName = $this->robotsTxtPathDirectory . self::DEFAULT_TEMPLATE_FILE_NAME;
        if (is_file($defaultTemplateFileName)) {
            return file_get_contents($defaultTemplateFileName);
        }

        return $this->getDefaultRobotsTxtContent();
    }

    private function getDefaultRobotsTxtContent(): string
    {
        return <<<TEXT
# www.robotstxt.org/
# www.google.com/support/webmasters/bin/answer.py?hl=en&answer=156449

User-agent: *

TEXT;
    }
}
