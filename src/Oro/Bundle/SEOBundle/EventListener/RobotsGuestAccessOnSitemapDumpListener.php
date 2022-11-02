<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Manager\RobotsTxtIndexingRulesBySitemapManager;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;

/**
 * Adds rules according to system configuration to the robots.txt file for a default website.
 */
class RobotsGuestAccessOnSitemapDumpListener
{
    /** @var RobotsTxtIndexingRulesBySitemapManager */
    private $robotsTxtIndexingRulesBySitemapManager;

    public function __construct(RobotsTxtIndexingRulesBySitemapManager $robotsTxtIndexingRulesBySitemapManager)
    {
        $this->robotsTxtIndexingRulesBySitemapManager = $robotsTxtIndexingRulesBySitemapManager;
    }

    public function onSitemapDumpStorage(OnSitemapDumpFinishEvent $event): void
    {
        if ($event->getWebsite()->isDefault()) {
            $this->robotsTxtIndexingRulesBySitemapManager->flush($event->getWebsite(), $event->getVersion());
        }
    }
}
