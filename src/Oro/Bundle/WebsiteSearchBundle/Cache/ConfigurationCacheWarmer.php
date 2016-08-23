<?php

namespace Oro\Bundle\WebsiteSearchBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\WebsiteSearchBundle\Provider\ConfigurationCacheProvider;

class ConfigurationCacheWarmer implements CacheWarmerInterface
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

    public function warmUp($cacheDir)
    {
        $this->provider->warmUpCache();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}
