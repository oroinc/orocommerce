<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FileCache;
use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\Cache\MultiGetCache;
use Doctrine\Common\Cache\MultiPutCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * URL key-value cache is designed to store URL caches in key-value based storage like redis
 * Avoid it's usage with filesystem caches as it may contain a lot of cached keys each of whick will be stored
 * in separate file. This may lead to exceeding inode FS limit
 */
class UrlKeyValueCache implements UrlCacheInterface, ClearableCache, FlushableCache
{
    /**
     * @var Cache
     */
    private $persistentCache;

    /**
     * @var Cache
     */
    private $localCache;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $changedKeys = [];

    public function __construct(Cache $persistentCache, Cache $localCache, Filesystem $filesystem)
    {
        $this->persistentCache = $persistentCache;
        $this->localCache = $localCache;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function has($routeName, $routeParameters, $localizationId = null): bool
    {
        $cacheKey = $this->getCacheKey(self::URL_KEY, $routeName, $routeParameters, $localizationId);

        return $this->localCache->contains($cacheKey) || $this->persistentCache->contains($cacheKey);
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
        $this->localCache->delete($urlCacheKey);
        $this->persistentCache->delete($urlCacheKey);

        // Clear Slug cache
        $slugCacheKey = $this->getCacheKey(self::SLUG_KEY, $routeName, $routeParameters, $localizationId);
        $this->localCache->delete($slugCacheKey);
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
        $changedData = $this->getChangedData();
        $this->saveMultiToPersistentCache($changedData);
        $this->clearLocalCache();
    }

    /**
     * @return array
     */
    protected function getChangedData()
    {
        if ($this->localCache instanceof MultiGetCache) {
            return $this->localCache->fetchMultiple(array_keys($this->changedKeys));
        }

        $changes = [];
        foreach (array_keys($this->changedKeys) as $changedKey) {
            $changes[$changedKey] = $this->localCache->fetch($changedKey);
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
        $url = $this->localCache->fetch($cacheKey);
        if ($url === false) {
            $url = $this->persistentCache->fetch($cacheKey);

            if ($url !== false) {
                $this->localCache->save($cacheKey, $url);
            }
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

        $this->localCache->save($cacheKey, $value);
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
        if ($this->localCache instanceof ClearableCache) {
            $this->localCache->deleteAll();
        }
    }

    protected function clearPersistentCache()
    {
        if ($this->persistentCache instanceof FileCache) {
            $cache = $this->persistentCache;
            $this->filesystem->remove($cache->getDirectory() . DIRECTORY_SEPARATOR . $cache->getNamespace());
        } elseif ($this->persistentCache instanceof ClearableCache) {
            $this->persistentCache->deleteAll();
        }
    }
}
