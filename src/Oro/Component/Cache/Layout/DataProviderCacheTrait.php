<?php

namespace Oro\Component\Cache\Layout;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Gives cache abilities to Layout Data Provider classes.
 * See example in CategoryProvider.
 */
trait DataProviderCacheTrait
{
    /** @var bool */
    private $enabled;

    /** @var int */
    private $cacheLifeTime;

    /** @var string */
    private $cacheKey = '';

    /** @var CacheProvider */
    private $cache;

    public function __construct()
    {
        $this->disableCache();
    }

    public function disableCache()
    {
        $this->enabled = false;
    }

    public function enableCache()
    {
        $this->enabled = true;
    }

    /**
     * @param CacheProvider $cache
     * @param int $lifeTime
     */
    public function setCache(CacheProvider $cache = null, $lifeTime = 0)
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;

        if ($cache) {
            $this->enableCache();
        }
    }

    /**
     * @param array $parts
     * @return string
     */
    private function initCache(array $parts)
    {
        $key = sprintf(
            'cacheVal_%s',
            implode('_', $parts)
        );

        $this->cacheKey = $key;
    }

    /**
     * @return false|array
     */
    private function getFromCache()
    {
        if (!$this->cacheKey) {
            throw new \RuntimeException('Please init this cache first');
        }

        $result = $this->cache->fetch($this->cacheKey);

        if ($result) {
            // serialize() is slow and memory hungry
            $result = json_decode($result, true);
        }

        return $result;
    }

    /**
     * @param array $result
     */
    private function saveToCache($result)
    {
        if (!$this->cacheKey) {
            throw new \RuntimeException('Please init this cache first');
        }

        $result = json_encode($result);

        $this->storeCacheKeyInBunch($this->cacheKey);

        $this->cache->save($this->cacheKey, $result, $this->cacheLifeTime);
    }

    /**
     * @return bool
     */
    private function isCacheUsed()
    {
        return $this->enabled;
    }
    
    /**
     * @param string $key
     */
    private function storeCacheKeyInBunch($key)
    {
        $keyBunch = $this->cache->fetch('_keyBunch');

        $keyBunch = !$keyBunch ? [] : (array)json_decode($keyBunch, true);

        $keyBunch[] = $key;

        $keyBunch = json_encode($keyBunch);

        $this->cache->save('_keyBunch', $keyBunch);
    }
}
