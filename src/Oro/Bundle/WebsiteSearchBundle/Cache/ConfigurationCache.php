<?php

namespace Oro\Bundle\WebsiteSearchBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationCachedLoader;

class ConfigurationCache implements CacheClearerInterface, CacheWarmerInterface
{
    /**
     * @var MappingConfigurationCachedLoader
     */
    protected $loader;

    /**
     * @param MappingConfigurationCachedLoader $loader
     */
    public function __construct(MappingConfigurationCachedLoader $loader)
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
