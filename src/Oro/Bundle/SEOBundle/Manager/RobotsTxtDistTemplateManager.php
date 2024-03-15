<?php

namespace Oro\Bundle\SEOBundle\Manager;

use Oro\Component\Website\WebsiteInterface;

/**
 * Simplifies work with robots.txt.dist template.
 */
class RobotsTxtDistTemplateManager
{
    private const DEFAULT_TEMPLATE_FILE_NAME = 'robots.txt.dist';

    public function __construct(
        private RobotsTxtFileManager $robotsTxtFileManager,
        private string $robotsTxtPathDirectory
    ) {
    }

    public function isDistTemplateFileExist(WebsiteInterface $website): bool
    {
        $domainFileName = $this->robotsTxtFileManager->getFileNameByWebsite($website);
        $websiteTemplateFileName = $this->robotsTxtPathDirectory . $domainFileName . '.dist';

        return is_file($websiteTemplateFileName);
    }

    public function getDistTemplateContent(?WebsiteInterface $website = null): string
    {
        if ($website) {
            $websiteTemplateFileName = $this->getWebsiteDistTemplateFileName($website);
            if (is_file($websiteTemplateFileName)) {
                return file_get_contents($websiteTemplateFileName);
            }
        }

        return $this->getDistDefaultTemplateContent();
    }

    public function getDistDefaultTemplateContent(): string
    {
        $defaultTemplateFileName = $this->robotsTxtPathDirectory . self::DEFAULT_TEMPLATE_FILE_NAME;
        if (is_file($defaultTemplateFileName)) {
            return file_get_contents($defaultTemplateFileName);
        }

        return $this->getDefaultRobotsTxtContent();
    }

    private function getWebsiteDistTemplateFileName(WebsiteInterface $website): string
    {
        $domainFileName = $this->robotsTxtFileManager->getFileNameByWebsite($website);

        return $this->robotsTxtPathDirectory . $domainFileName . '.dist';
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
