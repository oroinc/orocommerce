<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Psr\Cache\CacheItemPoolInterface;

/**
 * URL key-value cache is designed to store URL caches in key-value based storage like redis
 * Avoid its usage with filesystem caches as it may contain a lot of cached keys each of which will be stored
 * in separate file. This may lead to exceeding inode FS limit
 */
class UrlKeyValueCache implements UrlCacheInterface, FlushableCacheInterface
{
    private CacheItemPoolInterface $persistentCache;
    private CacheItemPoolInterface $localCache;
    private array $changedKeys = [];

    public function __construct(CacheItemPoolInterface $persistentCache, CacheItemPoolInterface $localCache)
    {
        $this->persistentCache = $persistentCache;
        $this->localCache = $localCache;
    }

    public function has($routeName, $routeParameters, $localizationId = null): bool
    {
        $cacheKey = $this->getCacheKey(self::URL_KEY, $routeName, $routeParameters, $localizationId);

        return $this->localCache->hasItem($cacheKey) || $this->persistentCache->hasItem($cacheKey);
    }

    public function getUrl($routeName, $routeParameters, $localizationId = null) : false|string
    {
        return $this->getFromCacheByType(self::URL_KEY, $routeName, $routeParameters, $localizationId);
    }

    public function getSlug($routeName, $routeParameters, $localizationId = null) : false|string
    {
        return $this->getFromCacheByType(self::SLUG_KEY, $routeName, $routeParameters, $localizationId);
    }

    public function setUrl($routeName, $routeParameters, $url, $slug = null, $localizationId = null) : void
    {
        $this->saveToCacheByType($url, self::URL_KEY, $routeName, $routeParameters, $localizationId);
        if ($slug) {
            $this->saveToCacheByType($slug, self::SLUG_KEY, $routeName, $routeParameters, $localizationId);
        }
    }

    public function removeUrl($routeName, $routeParameters, $localizationId = null) : void
    {
        // Clear URL cache
        $urlCacheKey = $this->getCacheKey(self::URL_KEY, $routeName, $routeParameters, $localizationId);
        $this->localCache->deleteItem($urlCacheKey);
        $this->persistentCache->deleteItem($urlCacheKey);

        // Clear Slug cache
        $slugCacheKey = $this->getCacheKey(self::SLUG_KEY, $routeName, $routeParameters, $localizationId);
        $this->localCache->deleteItem($slugCacheKey);
        $this->persistentCache->deleteItem($slugCacheKey);
    }

    public function deleteAll() : void
    {
        $this->clearLocalCache();
        $this->clearPersistentCache();
    }

    public function flushAll() : void
    {
        $this->saveMultiToPersistentCache($this->getChangedData());
        $this->clearLocalCache();
    }

    protected function getChangedData() : array
    {
        $changes = [];
        foreach ($this->localCache->getItems(array_keys($this->changedKeys)) as $key => $item) {
            $changes[$key] = $item->get();
        }

        return $changes;
    }

    protected function saveMultiToPersistentCache(array $values) : void
    {
        if (empty($values)) {
            return;
        }
        foreach ($values as $cacheKey => $cacheValue) {
            $cacheItem = $this->persistentCache->getItem($cacheKey);
            $cacheItem->set($cacheValue);
            $this->persistentCache->saveDeferred($cacheItem);
        }
        $this->persistentCache->commit();
    }

    protected function getFromCacheByType(
        string $type,
        string $routeName,
        array $routeParameters,
        null|int $localizationId = null
    ) : string|false {
        $cacheKey = $this->getCacheKey($type, $routeName, $routeParameters, $localizationId);
        $urlItem = $this->localCache->getItem($cacheKey);
        if ($urlItem->isHit()) {
            return $urlItem->get() ?? false;
        }

        $cacheItem = $this->persistentCache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            $urlItem->set($cacheItem->get());
            $this->localCache->save($urlItem);
            return $cacheItem->get() ?? false;
        }

        return false;
    }

    protected function saveToCacheByType(
        string|null $value,
        string $type,
        string $routeName,
        array $routeParameters,
        null|int $localizationId = null
    ) : void {
        $cacheKey = $this->getCacheKey($type, $routeName, $routeParameters, $localizationId);
        $this->changedKeys[$cacheKey] = true;

        $urlItem = $this->localCache->getItem($cacheKey);
        $urlItem->set($value);
        $this->localCache->save($urlItem);
    }

    protected function getCacheKey(
        string $type,
        string $routeName,
        array $routeParameters,
        null|int $localizationId = null
    ) : string {
        return UniversalCacheKeyGenerator::normalizeCacheKey(
            implode('_', [$routeName, base64_encode(serialize($routeParameters)), (int)$localizationId, $type])
        );
    }

    protected function clearLocalCache() : void
    {
        $this->localCache->clear();
        $this->changedKeys = [];
    }

    protected function clearPersistentCache() : void
    {
        $this->persistentCache->clear();
    }
}
