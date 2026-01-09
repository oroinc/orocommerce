<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;

/**
 * Schedules sitemap regeneration when guest access settings change.
 *
 * This listener monitors system configuration changes and triggers sitemap regeneration whenever the guest access
 * setting is modified. This ensures that the sitemap is kept in sync with the current guest access policy,
 * as the set of accessible URLs may change based on whether guest access is enabled or disabled.
 */
class ScheduleSitemapGenerationOnGuestAccessChangeListener
{
    /**
     * @var SitemapGenerationScheduler
     */
    private $sitemapGenerationScheduler;

    public function __construct(SitemapGenerationScheduler $sitemapGenerationScheduler)
    {
        $this->sitemapGenerationScheduler = $sitemapGenerationScheduler;
    }

    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        if (!$event->isChanged('oro_frontend.guest_access_enabled')) {
            return;
        }

        $this->sitemapGenerationScheduler->scheduleSend();
    }
}
