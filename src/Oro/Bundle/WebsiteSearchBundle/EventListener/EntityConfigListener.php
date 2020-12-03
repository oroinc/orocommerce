<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

/**
 * Clears website search mapping cache on changes in entity config
 */
class EntityConfigListener
{
    /**
     * @var WebsiteSearchMappingProvider
     */
    private $searchMappingProvider;

    /**
     * @param WebsiteSearchMappingProvider $searchMappingProvider
     */
    public function __construct(WebsiteSearchMappingProvider $searchMappingProvider)
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    public function clearMappingCache()
    {
        $this->searchMappingProvider->clearCache();
    }
}
