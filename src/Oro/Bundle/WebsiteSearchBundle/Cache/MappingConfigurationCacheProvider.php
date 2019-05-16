<?php

namespace Oro\Bundle\WebsiteSearchBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Mapping cache provider encapsulates logic of fetching, saving and deleting configuration from cache.
 */
class MappingConfigurationCacheProvider
{
    private const CACHE_KEY_HASH = 'cache_key_hash';
    private const CACHE_KEY_CONFIGURATION = 'cache_key_configuration';

    /**
     * @var array
     */
    private $cachedValues;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @return array
     */
    public function fetchConfiguration(): array
    {
        if (null !== $this->cachedValues) {
            return $this->cachedValues;
        }

        $cachedValues = $this->cacheProvider->fetchMultiple([self::CACHE_KEY_HASH, self::CACHE_KEY_CONFIGURATION]);

        if (count($cachedValues) === 2) {
            $this->cachedValues = [
                $cachedValues[self::CACHE_KEY_HASH],
                $cachedValues[self::CACHE_KEY_CONFIGURATION]
            ];
        } else {
            $this->cachedValues = [false, []];
        }

        return $this->cachedValues;
    }

    /**
     * @param string $hash
     * @param array $configuration
     */
    public function saveConfiguration(string $hash, array $configuration): void
    {
        $this->cachedValues = null;
        $this->cacheProvider->saveMultiple([
            self::CACHE_KEY_HASH => $hash,
            self::CACHE_KEY_CONFIGURATION => $configuration
        ]);
    }

    public function deleteConfiguration(): void
    {
        $this->cachedValues = null;
        $this->cacheProvider->deleteMultiple([self::CACHE_KEY_HASH, self::CACHE_KEY_CONFIGURATION]);
    }
}
