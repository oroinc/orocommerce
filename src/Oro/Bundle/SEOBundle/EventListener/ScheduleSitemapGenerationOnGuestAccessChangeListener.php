<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;

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
