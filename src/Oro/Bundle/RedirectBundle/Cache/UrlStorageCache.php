<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FileCache;
use Symfony\Component\Filesystem\Filesystem;

class UrlStorageCache
{
    /**
     * @var FileCache
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
     * @param FileCache $persistentCache
     * @param Cache $localCache
     * @param Filesystem $filesystem
     */
    public function __construct(FileCache $persistentCache, Cache $localCache, Filesystem $filesystem)
    {
        $this->persistentCache = $persistentCache;
        $this->localCache = $localCache;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $routeName
     * @param array $parameters
     * @return string
     */
    public static function getCacheKey($routeName, $parameters)
    {
        $diffKey = md5(serialize($parameters))[0];

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
    public function getUrlDataStorage($routeName, $routeParameters)
    {
        $key = self::getCacheKey($routeName, $routeParameters);
        $this->usedKeys[] = $key;
        if (!$this->localCache->contains($key)) {
            $storage = $this->persistentCache->fetch($key);

            if (!$storage instanceof UrlDataStorage) {
                $storage = new UrlDataStorage();
            }

            $this->localCache->save($key, $storage);
        }

        return $this->localCache->fetch($key);
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @return null|string
     */
    public function getUrl($routeName, $routeParameters)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);

        return $storage->getUrl($routeParameters);
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @return null|string
     */
    public function getSlug($routeName, $routeParameters)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);

        return $storage->getSlug($routeParameters);
    }

    /**
     * Set URL to local cache.To save changes to persistent cache call flush().
     *
     * @param string $routeName
     * @param array $routeParameters
     * @param string $url
     * @param string|null $slug
     */
    public function setUrl($routeName, $routeParameters, $url, $slug = null)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);
        $storage->setUrl($routeParameters, $url, $slug);
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     */
    public function removeUrl($routeName, $routeParameters)
    {
        $storage = $this->getUrlDataStorage($routeName, $routeParameters);
        $storage->removeUrl($routeParameters);
    }

    /**
     * Flush changes from local cache to persistent cache.
     */
    public function flush()
    {
        foreach ($this->usedKeys as $key) {
            if ($this->localCache->contains($key)) {
                $localStorage = $this->localCache->fetch($key);
                if ($this->persistentCache->contains($key)) {
                    /** @var UrlDataStorage $urlDataStorage */
                    $urlDataStorage = $this->persistentCache->fetch($key);
                    $urlDataStorage->merge($localStorage);
                }

                $this->persistentCache->save($key, $localStorage);
                $this->localCache->delete($key);
            }
        }
    }

    public function deleteAll()
    {
        if ($this->localCache instanceof ClearableCache) {
            $this->localCache->deleteAll();
        }

        $cache = $this->persistentCache;
        $this->filesystem->remove($cache->getDirectory() . DIRECTORY_SEPARATOR . $cache->getNamespace());
    }
}
