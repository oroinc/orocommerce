<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

/**
 * Clears website search mapping cache when entity config is changed.
 */
class EntityConfigListener
{
    /** @var SearchMappingProvider */
    private $searchMappingProvider;

    public function __construct(SearchMappingProvider $searchMappingProvider)
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    public function clearMappingCache(): void
    {
        $this->searchMappingProvider->clearCache();
    }
}
