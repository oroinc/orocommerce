<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Oro\Bundle\CacheBundle\Provider\DirectoryAwareFileCacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * URL storage cache is designed to store caches in groups which decreases number of cached files and saves space and
 * used inode count. $splitDeep should be adjusted based on number of cached keys, use 2 for numbers lower 1M and higher
 * values for exceeding counts
 */
class UrlStorageCache implements UrlCacheInterface, ClearableCacheInterface, FlushableCacheInterface
{
    private const DEFAULT_SPLIT_DEEP = 2;

    private CacheItemPoolInterface $persistentCache;
    private CacheItemPoolInterface $localCache;
    private Filesystem $filesystem;
    private array $usedKeys = [];
    private int $splitDeep;

    /**
     * @param CacheItemPoolInterface $persistentCache
     * @param CacheItemPoolInterface $localCache
     * @param Filesystem $filesystem
     * @param int $splitDeep
     */
    public function __construct(
        CacheItemPoolInterface $persistentCache,
        CacheItemPoolInterface $localCache,
        Filesystem $filesystem,
        int $splitDeep = self::DEFAULT_SPLIT_DEEP
    ) {
        $this->persistentCache = $persistentCache;
        $this->localCache = $localCache;
        $this->filesystem = $filesystem;
        $this->splitDeep = $splitDeep > 0 ? $splitDeep : self::DEFAULT_SPLIT_DEEP;
    }

    /**
     * {@inheritdoc}
     */
    public function has($routeName, $routeParameters, $localizationId = null): bool
    {
        $key = $this->getCacheKey($routeName, $routeParameters);

        return $this->localCache->hasItem($key) || $this->persistentCache->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($routeName, $routeParameters, $localizationId = null)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);

        return $storage->getUrl($routeParameters, $localizationId);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlug($routeName, $routeParameters, $localizationId = null)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);

        return $storage->getSlug($routeParameters, $localizationId);
    }

    /**
     * Set URL to local cache.To save changes to persistent cache call flushAll().
     */
    public function setUrl($routeName, $routeParameters, $url, $slug = null, $localizationId = null)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);
        $storage->setUrl($routeParameters, $url, $slug, $localizationId);
    }

    /**
     * Remove URL from local and persistent caches. To save changes to persistent cache call flushAll().
     */
    public function removeUrl($routeName, $routeParameters, $localizationId = null)
    {
        $this->getUrlDataStorageFromPersistentStorage($routeName, $routeParameters)
            ?->removeUrl($routeParameters, $localizationId);

        $this->getUrlDataStorageFromLocalStorage($routeName, $routeParameters)
            ?->removeUrl($routeParameters, $localizationId);
    }

    /**
     * Move collected changes from local cache to persistent cache and save changes.
     */
    public function flushAll() : void
    {
        foreach (array_keys($this->usedKeys) as $cacheKey) {
            // Item isn't present in local cache. Nothing to move to persistent storage, continue.
            $localCacheItem = $this->localCache->getItem($cacheKey);
            if (!$localCacheItem->isHit()) {
                continue;
            }

            $localUrlStorage = $localCacheItem->get();
            $persistentCacheItem = $this->persistentCache->getItem($cacheKey);
            if ($persistentCacheItem->isHit()) {
                // Cache key is present in persistent cache. If it is not instance of UrlDataStorage - create new.
                $persistentUrlStorage = $persistentCacheItem->get();
                if (!$persistentUrlStorage instanceof UrlDataStorage) {
                    $persistentUrlStorage = new UrlDataStorage();
                }
                // Merge URLs data from local storage with URL data in persistent storage.
                $persistentUrlStorage->merge($localUrlStorage);
            } else {
                // Cache key isn't present in persistent storage. Save whole local URLs data to persistent storage.
                $persistentCacheItem->set($localUrlStorage);
            }
            // Commit changes to persistent cache.
            $this->persistentCache->save($persistentCacheItem);
        }
        $this->usedKeys = [];
    }

    public function deleteAll() : void
    {
        $this->localCache->clear();
        if ($this->persistentCache instanceof DirectoryAwareFileCacheInterface
            && $cacheDir = $this->persistentCache->getDirectory()
        ) {
            $this->filesystem->remove($cacheDir);
        } elseif ($this->persistentCache instanceof CacheItemPoolInterface) {
            $this->persistentCache->clear();
        }
    }

    /**
     * @param string $routeName
     * @param array $parameters
     * @return string
     */
    protected function getCacheKey($routeName, $parameters)
    {
        $diffKey = substr(md5(serialize($parameters)), 0, $this->splitDeep);

        return implode('_', [$routeName, $diffKey]);
    }

    /**
     * Get UrlDataStorage instance.
     *
     * If it is not loaded and contains in persistent cache - instance from persistent cache will be returned.
     * For already loaded storage instance stored in local cache will be returned.
     *
     * @param string $routeName
     * @param array $routeParameters
     * @return UrlDataStorage
     */
    protected function getUrlDataStorage(string $routeName, array $routeParameters): UrlDataStorage
    {
        $cacheKey = $this->getCacheKey($routeName, $routeParameters);
        $this->usedKeys[$cacheKey] = true;

        $localCacheItem = $this->localCache->getItem($cacheKey);
        if (!$localCacheItem->isHit()) {
            $storage = $this->getUrlDataStorageFromPersistentStorage($routeName, $routeParameters);
            if (!$storage instanceof UrlDataStorage) {
                $storage = new UrlDataStorage();
            }
            $localCacheItem->set($storage);
            $this->localCache->save($localCacheItem);
        }

        return $localCacheItem->get();
    }

    private function getUrlDataStorageFromLocalStorage(string $routeName, array $routeParameters): ?UrlDataStorage
    {
        $cacheKey = $this->getCacheKey($routeName, $routeParameters);
        $persistentCacheItem = $this->localCache->getItem($cacheKey);
        if ($persistentCacheItem->isHit()) {
            return $persistentCacheItem->get();
        }

        return null;
    }

    private function getUrlDataStorageFromPersistentStorage(string $routeName, array $routeParameters): ?UrlDataStorage
    {
        $cacheKey = $this->getCacheKey($routeName, $routeParameters);
        $persistentCacheItem = $this->persistentCache->getItem($cacheKey);
        if ($persistentCacheItem->isHit()) {
            return $persistentCacheItem->get();
        }

        return null;
    }
}
