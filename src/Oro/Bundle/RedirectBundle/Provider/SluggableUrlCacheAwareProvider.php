<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;

/**
 * Get human readable URL by routeName, routeParameters and localizationId from Cache
 */
class SluggableUrlCacheAwareProvider implements SluggableUrlProviderInterface
{
    /**
     * @var UrlCacheInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $contextUrl;

    public function __construct(UrlCacheInterface $cache)
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
        $url = false;

        // For context aware URLs slug may be used as item part
        if ($this->contextUrl && $slug = $this->cache->getSlug($routeName, $routeParameters, $localizationId)) {
            $url = $slug;
        }

        // For URLs without context only full URL is acceptable
        if (!$url) {
            $url = $this->cache->getUrl($routeName, $routeParameters, $localizationId);
        }

        return $url;
    }
}
