<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;

/**
 * Get human readable URL by routeName, routeParameters and localizationId from Cache
 */
class SluggableUrlCacheAwareProvider implements SluggableUrlProviderInterface
{
    /**
     * @var UrlStorageCache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $contextUrl;

    /**
     * @param UrlStorageCache $cache
     */
    public function __construct(UrlStorageCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function setContextUrl($contextUrl)
    {
        $this->contextUrl = $contextUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($routeName, $routeParameters, $localizationId)
    {
        $urlDataStorage = $this->cache->getUrlDataStorage(
            $routeName,
            $routeParameters
        );

        $url = null;

        if ($urlDataStorage) {
            // For context aware URLs slug may be used as item part
            if ($this->contextUrl && $slug = $urlDataStorage->getSlug($routeParameters, $localizationId)) {
                $url = $slug;
            }

            // For URLs without context only full URL is acceptable
            if (!$url) {
                $url = $urlDataStorage->getUrl($routeParameters, $localizationId);
            }
        }

        return $url;
    }
}
