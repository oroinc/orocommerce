<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\Cache\MultiPutCache;
use Psr\Cache\CacheItemPoolInterface;

/**
 * URL key-value cache is designed to store URL caches in key-value based storage like redis
 * Avoid its usage with filesystem caches as it may contain a lot of cached keys each of which will be stored
 * in separate file. This may lead to exceeding inode FS limit
 */
class UrlKeyValueCache implements UrlCacheInterface, ClearableCache, FlushableCache
{
    private Cache $persistentCache;
    private CacheItemPoolInterface $localCache;
    private array $changedKeys = [];

    public function __construct(Cache $persistentCache, CacheItemPoolInterface $localCache)
    {
        $this->persistentCache = $persistentCache;
        $this->localCache = $localCache;
    }

    /**
     * {@inheritdoc}
     */
    public function has($routeName, $routeParameters, $localizationId = null): bool
    {
        $cacheKey = $this->getCacheKey(self::URL_KEY, $routeName, $routeParameters, $localizationId);

        return $this->localCache->hasItem($cacheKey) || $this->persistentCache->contains($cacheKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($routeName, $routeParameters, $localizationId = null)
    {
        return $this->getFromCacheByType(self::URL_KEY, $routeName, $routeParameters, $localizationId);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlug($routeName, $routeParameters, $localizationId = null)
    {
        return $this->getFromCacheByType(self::SLUG_KEY, $routeName, $routeParameters, $localizationId);
    }

    /**
     * {@inheritdoc}
     */
    public function setUrl($routeName, $routeParameters, $url, $slug = null, $localizationId = null)
    {
        $this->saveToCacheByType($url, self::URL_KEY, $routeName, $routeParameters, $localizationId);
        if ($slug) {
            $this->saveToCacheByType($slug, self::SLUG_KEY, $routeName, $routeParameters, $localizationId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeUrl($routeName, $routeParameters, $localizationId = null)
    {
        // Clear URL cache
        $urlCacheKey = $this->getCacheKey(self::URL_KEY, $routeName, $routeParameters, $localizationId);
        $this->localCache->deleteItem($urlCacheKey);
        $this->persistentCache->delete($urlCacheKey);

        // Clear Slug cache
        $slugCacheKey = $this->getCacheKey(self::SLUG_KEY, $routeName, $routeParameters, $localizationId);
        $this->localCache->deleteItem($slugCacheKey);
        $this->persistentCache->delete($slugCacheKey);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $this->clearLocalCache();
        $this->clearPersistentCache();
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        $this->saveMultiToPersistentCache($this->getChangedData());
        $this->clearLocalCache();
    }

    /**
     * @return array
     */
    protected function getChangedData()
    {
        $changes = [];
        foreach ($this->localCache->getItems(array_keys($this->changedKeys)) as $key => $item) {
            $changes[$key] = $item->get();
        }

        return $changes;
    }

    protected function saveMultiToPersistentCache(array $values)
    {
        if (empty($values)) {
            return;
        }

        if ($this->persistentCache instanceof MultiPutCache) {
            $this->persistentCache->saveMultiple($values);
        } else {
            foreach ($values as $key => $value) {
                $this->persistentCache->save($key, $value);
            }
        }
    }

    /**
     * @param string $type
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     * @return string|null|false
     */
    protected function getFromCacheByType($type, $routeName, $routeParameters, $localizationId = null)
    {
        $cacheKey = $this->getCacheKey($type, $routeName, $routeParameters, $localizationId);
        $urlItem = $this->localCache->getItem($cacheKey);
        if ($urlItem->isHit()) {
            return $urlItem->get();
        }

        $url = $this->persistentCache->fetch($cacheKey);
        if ($url !== false) {
            $urlItem->set($url);
            $this->localCache->save($urlItem);
        }

        return $url;
    }

    /**
     * @param string $value
     * @param string $type
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     */
    protected function saveToCacheByType($value, $type, $routeName, $routeParameters, $localizationId = null)
    {
        $cacheKey = $this->getCacheKey($type, $routeName, $routeParameters, $localizationId);
        $this->changedKeys[$cacheKey] = true;

        $urlItem = $this->localCache->getItem($cacheKey);
        $urlItem->set($value);
        $this->localCache->save($urlItem);
    }

    /**
     * @param string $type
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     * @return string
     */
    protected function getCacheKey($type, $routeName, $routeParameters, $localizationId = null)
    {
        return implode('_', [$routeName, base64_encode(serialize($routeParameters)), (int)$localizationId, $type]);
    }

    protected function clearLocalCache()
    {
        $this->localCache->clear();
        $this->changedKeys = [];
    }

    protected function clearPersistentCache()
    {
        if ($this->persistentCache instanceof ClearableCache) {
            $this->persistentCache->deleteAll();
        }
    }
}
