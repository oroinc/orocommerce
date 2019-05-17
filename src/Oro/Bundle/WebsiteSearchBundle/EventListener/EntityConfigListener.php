<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;

/**
 * Clears website search mapping cache on changes in entity config
 */
class EntityConfigListener
{
    /**
     * @var MappingConfigurationProvider
     */
    private $configurationProvider;

    /**
     * @param MappingConfigurationProvider $configurationProvider
     */
    public function __construct(MappingConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    public function clearMappingCache()
    {
        $this->configurationProvider->clearCache();
    }
}
