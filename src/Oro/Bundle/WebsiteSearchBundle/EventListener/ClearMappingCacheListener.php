<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Cache\MappingConfigurationCacheProvider;

/**
 * Clears website search mapping cache after database preparation
 */
class ClearMappingCacheListener
{
    /** @var MappingConfigurationCacheProvider */
    private $mappingCacheProvider;

    public function __construct(MappingConfigurationCacheProvider $mappingCacheProvider)
    {
        $this->mappingCacheProvider = $mappingCacheProvider;
    }

    public function onAfterDatabasePreparation()
    {
        $this->mappingCacheProvider->deleteConfiguration();
    }
}
