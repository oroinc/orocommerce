<?php

namespace Oro\Bundle\WebsiteSearchBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

use Oro\Bundle\WebsiteSearchBundle\Provider\ConfigurationCacheProvider;

class ConfigurationCacheClearer implements CacheClearerInterface
{
    /**
     * @var ConfigurationCacheProvider
     */
    protected $provider;

    /**
     * @param ConfigurationCacheProvider $provider
     */
    public function __construct(ConfigurationCacheProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $this->provider->clearCache();
    }
}
