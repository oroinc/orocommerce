<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Manager\RobotsTxtIndexingRulesBySitemapManager;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;

class RobotsGuestAccessOnSitemapDumpListener
{
    /**
     * @var RobotsTxtIndexingRulesBySitemapManager
     */
    private $robotsTxtIndexingRulesBySitemapManager;

    /**
     * @param RobotsTxtIndexingRulesBySitemapManager $robotsTxtIndexingRulesBySitemapManager
     */
    public function __construct(RobotsTxtIndexingRulesBySitemapManager $robotsTxtIndexingRulesBySitemapManager)
    {
        $this->robotsTxtIndexingRulesBySitemapManager = $robotsTxtIndexingRulesBySitemapManager;
    }

    /**
     * @param OnSitemapDumpFinishEvent $event
     */
    public function onSitemapDumpStorage(OnSitemapDumpFinishEvent $event)
    {
        if ($event->getWebsite()->isDefault()) {
            $this->robotsTxtIndexingRulesBySitemapManager->flush($event->getWebsite(), $event->getVersion());
        }
    }
}
