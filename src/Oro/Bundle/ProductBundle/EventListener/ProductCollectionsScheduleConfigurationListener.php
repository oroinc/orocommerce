<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\ProductBundle\Command\ProductCollectionsIndexCronCommand;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

/**
 * This listener is used to change cron schedule for product collections indexation when related
 * system configuration option is changed.
 */
class ProductCollectionsScheduleConfigurationListener
{
    const CONFIG_FIELD = Configuration::ROOT_NODE . '.' . Configuration::PRODUCT_COLLECTIONS_INDEXATION_CRON_SCHEDULE;

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
                ProductCollectionsIndexCronCommand::getDefaultName(),
                [],
                $event->getOldValue(self::CONFIG_FIELD)
            );
            $this->deferredScheduler->addSchedule(
                ProductCollectionsIndexCronCommand::getDefaultName(),
                [],
                $event->getNewValue(self::CONFIG_FIELD)
            );
            $this->deferredScheduler->flush();
        }
    }
}
