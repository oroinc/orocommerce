<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

/**
 * Schedules reindexation of search index for quote entity
 * when the "Enable Quote Project Name" config option is changed.
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
            if ('oro_sale.enable_quote_project_name' === $configKey) {
                $this->searchMappingProvider->clearCache();
                $this->searchIndexer->reindex(Quote::class);
                break;
            }
        }
    }
}
