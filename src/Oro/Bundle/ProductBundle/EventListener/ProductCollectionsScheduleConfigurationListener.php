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
        if ($event->isChanged($this->getScheduleFieldName())) {
            $this->deferredScheduler->removeSchedule(
                ProductCollectionsIndexCronCommand::NAME,
                [],
                $event->getOldValue($this->getScheduleFieldName())
            );
            $this->deferredScheduler->addSchedule(
                ProductCollectionsIndexCronCommand::NAME,
                [],
                $event->getNewValue($this->getScheduleFieldName())
            );
            $this->deferredScheduler->flush();
        }
    }

    /**
     * @return string
     */
    private function getScheduleFieldName()
    {
        return sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::PRODUCT_COLLECTIONS_INDEXATION_CRON_SCHEDULE);
    }
}
