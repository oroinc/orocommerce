<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\SEOBundle\Command\GenerateSitemapCommand;

/**
 * Updates deferred scheduler for GenerateSitemapCommand
 */
class UpdateCronDefinitionConfigListener
{
    const CONFIG_FIELD = 'oro_seo.sitemap_cron_definition';

    /**
     * @var DeferredScheduler
     */
    private $deferredScheduler;

    public function __construct(DeferredScheduler $deferredScheduler)
    {
        $this->deferredScheduler = $deferredScheduler;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(self::CONFIG_FIELD)) {
            $this->deferredScheduler->removeSchedule(
                GenerateSitemapCommand::getDefaultName(),
                [],
                $event->getOldValue(self::CONFIG_FIELD)
            );
            $this->deferredScheduler->addSchedule(
                GenerateSitemapCommand::getDefaultName(),
                [],
                $event->getNewValue(self::CONFIG_FIELD)
            );
            $this->deferredScheduler->flush();
        }
    }
}
