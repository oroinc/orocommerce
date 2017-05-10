<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\SEOBundle\Command\GenerateSitemapCommand;

class UpdateCronDefinitionConfigListener
{
    const CONFIG_FIELD = 'oro_seo.sitemap_cron_definition';

    /**
     * @var DeferredScheduler
     */
    private $deferredScheduler;

    /**
     * @param DeferredScheduler $deferredScheduler
     */
    public function __construct(DeferredScheduler $deferredScheduler)
    {
        $this->deferredScheduler = $deferredScheduler;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(self::CONFIG_FIELD)) {
            $this->deferredScheduler->removeSchedule(
                GenerateSitemapCommand::NAME,
                [],
                $event->getOldValue(self::CONFIG_FIELD)
            );
            $this->deferredScheduler->addSchedule(
                GenerateSitemapCommand::NAME,
                [],
                $event->getNewValue(self::CONFIG_FIELD)
            );
            $this->deferredScheduler->flush();
        }
    }
}
