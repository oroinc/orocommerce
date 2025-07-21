<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

/**
 * Schedules reindexation of search index for RFQ entity
 * when the "Enable RFQ Project Name" config option is changed.
 */
class ChangeProjectNameConfigurationListener
{
    public function __construct(
        private readonly SearchMappingProvider $searchMappingProvider,
        private readonly IndexerInterface $searchIndexer
    ) {
    }

    public function onUpdateAfter(ConfigUpdateEvent $event): void
    {
        $changeSet = $event->getChangeSet();
        foreach ($changeSet as $configKey => $change) {
            if ('oro_rfp.enable_rfq_project_name' === $configKey) {
                $this->searchMappingProvider->clearCache();
                $this->searchIndexer->reindex(Request::class);
                break;
            }
        }
    }
}
