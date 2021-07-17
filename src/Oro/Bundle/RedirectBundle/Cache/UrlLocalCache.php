<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;

/**
 * Url local cache may be used to store URLs on per request basis and may be used in pair with database URL provider
 * to always fetch Semantic URLs from DB without being saved in persistent cache
 */
class UrlLocalCache implements UrlCacheInterface, ClearableCache
{
    /**
     * @var Cache
     */
    protected $localCache;

    public function __construct(Cache $localCache)
    {
        $this->localCache = $localCache;
    }

    /**
     * {@inheritdoc}
     */
    public function has($routeName, $routeParameters, $localizationId = null): bool
    {
        return $this->localCache->contains($this->getCacheKey($routeName, $routeParameters, $localizationId));
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     * @return null|string
     */
    public function getUrl($routeName, $routeParameters, $localizationId = null)
    {
        $data = $this->localCache->fetch($this->getCacheKey($routeName, $routeParameters, $localizationId));
        if ($data && array_key_exists(self::URL_KEY, $data)) {
            return $data[self::URL_KEY];
        }

        return false;
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     * @return null|string
     */
    public function getSlug($routeName, $routeParameters, $localizationId = null)
    {
        $data = $this->localCache->fetch($this->getCacheKey($routeName, $routeParameters, $localizationId));
        if (!empty($data[self::SLUG_KEY])) {
            return $data[self::SLUG_KEY];
        }

        return false;
    }

    /**
     * Set URL to local cache.To save changes to persistent cache call flush().
     *
     * @param string $routeName
     * @param array $routeParameters
     * @param string $url
     * @param string|null $slug
     * @param null|int $localizationId
     */
    public function setUrl($routeName, $routeParameters, $url, $slug = null, $localizationId = null)
    {
        $this->localCache->save(
            $this->getCacheKey($routeName, $routeParameters, $localizationId),
            [
                self::URL_KEY => $url,
                self::SLUG_KEY => $slug
            ]
        );
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     */
    public function removeUrl($routeName, $routeParameters, $localizationId = null)
    {
        $this->localCache->delete($this->getCacheKey($routeName, $routeParameters, $localizationId));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        if ($this->localCache instanceof ClearableCache) {
            $this->localCache->deleteAll();
        }
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     * @return string
     */
    protected function getCacheKey($routeName, $routeParameters, $localizationId = null)
    {
        return implode('_', [$routeName, base64_encode(serialize($routeParameters)), (int)$localizationId]);
    }
}
