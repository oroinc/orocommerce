<?php

namespace Oro\Bundle\RedirectBundle\Cache;

/**
 * Interface for URL Caches
 */
interface UrlCacheInterface
{
    const SLUG_KEY = 's';
    const URL_KEY = 'u';
    const SLUG_ROUTES_KEY = '__slug_routes__';

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     * @return bool
     */
    public function has($routeName, $routeParameters, $localizationId = null): bool;

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     * @return null|string
     */
    public function getUrl($routeName, $routeParameters, $localizationId = null);

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     * @return null|string
     */
    public function getSlug($routeName, $routeParameters, $localizationId = null);

    /**
     * Set URL to local cache.To save changes to persistent cache call flush().
     *
     * @param string $routeName
     * @param array $routeParameters
     * @param string $url
     * @param string|null $slug
     * @param null|int $localizationId
     */
    public function setUrl($routeName, $routeParameters, $url, $slug = null, $localizationId = null);

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param null|int $localizationId
     */
    public function removeUrl($routeName, $routeParameters, $localizationId = null);
}
