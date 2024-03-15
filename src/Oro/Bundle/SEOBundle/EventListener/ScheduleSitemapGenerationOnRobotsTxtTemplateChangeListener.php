<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;
use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;

/**
 * Schedules generation of sitemap files if Robots.txt Template was changed.
 */
class ScheduleSitemapGenerationOnRobotsTxtTemplateChangeListener
{
    public function __construct(
        private SitemapGenerationScheduler $sitemapGenerationScheduler
    ) {
    }

    public function onConfigUpdate(ConfigUpdateEvent $event): void
    {
        $robotsTxtTemplateKey = TreeUtils::getConfigKey(Configuration::ROOT_NODE, Configuration::ROBOTS_TXT_TEMPLATE);

        if (!$event->isChanged($robotsTxtTemplateKey)) {
            return;
        }

        $this->sitemapGenerationScheduler->scheduleSend();
    }
}
