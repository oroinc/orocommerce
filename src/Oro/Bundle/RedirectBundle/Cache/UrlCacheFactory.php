<?php

namespace Oro\Bundle\RedirectBundle\Cache;

/**
 * Factory for managing and retrieving URL cache implementations.
 *
 * This factory maintains a registry of URL cache implementations keyed by type and provides
 * access to the currently configured cache type. It allows different cache strategies to be
 * registered and retrieved based on the application's configuration.
 */
class UrlCacheFactory
{
    /**
     * @var array|UrlCacheInterface[]
     */
    protected $caches = [];

    /**
     * @var string
     */
    protected $currentCacheType;

    /**
     * @param string $currentCacheType
     */
    public function __construct($currentCacheType)
    {
        $this->currentCacheType = $currentCacheType;
    }

    /**
     * @param string $type
     * @param UrlCacheInterface $cache
     */
    public function registerCache($type, UrlCacheInterface $cache)
    {
        $this->caches[$type] = $cache;
    }

    /**
     * @return UrlCacheInterface
     */
    public function get()
    {
        if (!array_key_exists($this->currentCacheType, $this->caches)) {
            throw new \RuntimeException(
                sprintf(
                    'There is no UrlCache registered for type %s. Known types: %s',
                    $this->currentCacheType,
                    implode(', ', array_keys($this->caches))
                )
            );
        }

        return $this->caches[$this->currentCacheType];
    }
}
