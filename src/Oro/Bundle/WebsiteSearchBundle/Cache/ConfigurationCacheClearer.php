<?php

namespace Oro\Bundle\WebsiteSearchBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class ConfigurationCacheClearer implements CacheClearerInterface
{
    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $this->cacheProvider->deleteAll();
    }
}
