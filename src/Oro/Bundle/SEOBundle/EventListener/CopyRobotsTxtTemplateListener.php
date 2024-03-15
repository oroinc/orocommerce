<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtTemplateManager;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;

/**
 * Dumps the robots.txt file if a robots.txt file was not dumped yet.
 */
class CopyRobotsTxtTemplateListener
{
    public function __construct(
        private RobotsTxtFileManager $robotsTxtFileManager,
        private RobotsTxtTemplateManager $robotsTxtTemplateManager
    ) {
    }

    public function onSitemapDumpStorage(OnSitemapDumpFinishEvent $event): void
    {
        $website = $event->getWebsite();

        if ($this->robotsTxtFileManager->isContentFileExist($website)) {
            // We should not rewrite already dumped file because another website have a similar domain
            // and the template was already dumped.
            return;
        }

        $content = $this->robotsTxtTemplateManager->getTemplateContent($website);
        $this->robotsTxtFileManager->dumpContent($content, $website);
    }
}
