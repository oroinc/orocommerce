<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;

/**
 * Clears website search mapping cache after database preparation
 */
class ClearMappingCacheListener
{
    /**
     * @var MappingConfigurationProvider
     */
    private $mappingConfigurationProvider;

    public function __construct(MappingConfigurationProvider $mappingConfigurationProvider)
    {
        $this->mappingConfigurationProvider = $mappingConfigurationProvider;
    }

    public function onAfterDatabasePreparation()
    {
        $this->mappingConfigurationProvider->clearCache();
    }
}
