<?php

namespace Oro\Bundle\WebsiteSearchBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationCacheLoader;

class ConfigurationCache implements CacheClearerInterface, CacheWarmerInterface
{
    /**
     * @var MappingConfigurationCacheLoader
     */
    protected $loader;

    /**
     * @param MappingConfigurationCacheLoader $loader
     */
    public function __construct(MappingConfigurationCacheLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->loader->warmUpCache();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $this->loader->clearCache();
    }
}
