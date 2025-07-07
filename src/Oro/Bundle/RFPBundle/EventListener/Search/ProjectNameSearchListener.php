<?php

namespace Oro\Bundle\RFPBundle\EventListener\Search;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

/**
 * Adds "projectName" field of RFQ entity to search index
 * when the "Enable RFQ Project Name" config option is enabled.
 */
class ProjectNameSearchListener
{
    public function __construct(
        private readonly ConfigManager $configManager
    ) {
    }

    public function collectEntityMapEvent(SearchMappingCollectEvent $event): void
    {
        if (!$this->configManager->get('oro_rfp.enable_rfq_project_name')) {
            return;
        }

        $mapConfig = $event->getMappingConfig();
        $mapConfig[Request::class]['fields'][] = [
            'name' => 'projectName',
            'target_type' => 'text',
            'target_fields' => ['projectName']
        ];
        $event->setMappingConfig($mapConfig);
    }
}
