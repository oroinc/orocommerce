<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Cache\MappingConfigurationCacheProvider;

/**
 * Clears website search mapping cache on changes in entity config
 */
class EntityConfigListener
{
    /**
     * @var MappingConfigurationCacheProvider
     */
    private $cacheProvider;

    /**
     * @param MappingConfigurationCacheProvider $cacheProvider
     */
    public function __construct(MappingConfigurationCacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    public function clearMappingCache()
    {
        $this->cacheProvider->deleteConfiguration();
    }
}
