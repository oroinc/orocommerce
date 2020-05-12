<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FileCache;
use Doctrine\Common\Cache\FlushableCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * URL storage cache is designed to store caches in groups which decreases number of cached files and saves space and
 * used inode count. $splitDeep should be adjusted based on number of cached keys, use 2 for numbers lower 1M and higher
 * values for exceeding counts
 */
class UrlStorageCache implements UrlCacheInterface, ClearableCache, FlushableCache
{
    const DEFAULT_SPLIT_DEEP = 2;

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
    private $usedKeys = [];

    /**
     * @var int
     */
    private $splitDeep;

    /**
     * @param Cache $persistentCache
     * @param Cache $localCache
     * @param Filesystem $filesystem
     * @param int $splitDeep
     */
    public function __construct(
        Cache $persistentCache,
        Cache $localCache,
        Filesystem $filesystem,
        $splitDeep = self::DEFAULT_SPLIT_DEEP
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

        return $this->localCache->contains($key) || $this->persistentCache->contains($key);
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
     * {@inheritdoc}
     */
    public function setUrl($routeName, $routeParameters, $url, $slug = null, $localizationId = null)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);
        $storage->setUrl($routeParameters, $url, $slug, $localizationId);
    }

    /**
     * {@inheritdoc}
     */
    public function removeUrl($routeName, $routeParameters, $localizationId = null)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);
        $storage->removeUrl($routeParameters, $localizationId);
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        foreach (array_keys($this->usedKeys) as $key) {
            if ($this->localCache->contains($key)) {
                $localStorage = $this->localCache->fetch($key);
                if ($this->persistentCache->contains($key)) {
                    /** @var UrlDataStorage $urlDataStorage */
                    $urlDataStorage = $this->persistentCache->fetch($key);
                    $urlDataStorage->merge($localStorage);
                }

                $this->persistentCache->save($key, $localStorage);
            }
        }
        $this->usedKeys = [];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        if ($this->localCache instanceof ClearableCache) {
            $this->localCache->deleteAll();
        }

        if ($this->persistentCache instanceof FileCache) {
            $cache = $this->persistentCache;
            $this->filesystem->remove($cache->getDirectory() . DIRECTORY_SEPARATOR . $cache->getNamespace());
        } elseif ($this->persistentCache instanceof ClearableCache) {
            $this->persistentCache->deleteAll();
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
     * @return UrlDataStorage|false
     */
    protected function getUrlDataStorage($routeName, $routeParameters)
    {
        $key = $this->getCacheKey($routeName, $routeParameters);
        $this->usedKeys[$key] = true;
        $storage = $this->localCache->fetch($key);
        if ($storage === false) {
            $storage = $this->persistentCache->fetch($key);

            if (!$storage instanceof UrlDataStorage) {
                $storage = new UrlDataStorage();
            }

            $this->localCache->save($key, $storage);
        }

        return $storage;
    }
}
