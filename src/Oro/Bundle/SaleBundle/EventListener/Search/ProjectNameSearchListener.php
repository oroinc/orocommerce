<?php

namespace Oro\Bundle\SaleBundle\EventListener\Search;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

/**
 * Adds "projectName" field of quote entity to search index
 * when the "Enable Quote Project Name" config option is enabled.
 */
class ProjectNameSearchListener
{
    public function __construct(
        private readonly ConfigManager $configManager
    ) {
    }

    public function collectEntityMapEvent(SearchMappingCollectEvent $event): void
    {
        if (!$this->configManager->get('oro_sale.enable_quote_project_name')) {
            return;
        }

        $mapConfig = $event->getMappingConfig();
        $mapConfig[Quote::class]['fields'][] = [
            'name' => 'projectName',
            'target_type' => 'text',
            'target_fields' => ['projectName']
        ];
        $event->setMappingConfig($mapConfig);
    }
}
