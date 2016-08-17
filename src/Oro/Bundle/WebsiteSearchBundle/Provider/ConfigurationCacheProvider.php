<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Component\Config\CumulativeResourceInfo;

class ConfigurationCacheProvider
{
    const CACHE_KEY_HASH = 'cache_key_hash';
    const CACHE_KEY_CONFIGURATION = 'cache_key_configuration';

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var CacheProvider
     */
    protected $cacheProvider;

    /**
     * @var ConfigurationLoaderInterface
     */
    protected $configurationProvider;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var CumulativeResourceInfo[]
     */
    protected $resources;

    /**
     * @var ResourcesHashProvider
     */
    protected $hashProvider;

    /**
     * @param CacheProvider $cacheProvider
     * @param ConfigurationLoaderInterface $configurationProvider
     * @param ResourcesHashProvider $hashProvider,
     * @param bool $debug
     */
    public function __construct(
        CacheProvider $cacheProvider,
        ConfigurationLoaderInterface $configurationProvider,
        ResourcesHashProvider $hashProvider,
        $debug
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->cacheProvider = $cacheProvider;
        $this->hashProvider = $hashProvider;
        $this->debug = (bool) $debug;
    }

    /**
     * @return CumulativeResourceInfo[]
     */
    protected function getResources()
    {
        if (null === $this->resources) {
            $this->resources = $this->configurationProvider->getResources();
        }

        return $this->resources;
    }

    /**
     * @return bool
     */
    protected function isFresh()
    {
        $containsResources = $this->cacheProvider->contains(self::CACHE_KEY_HASH);
        if (!$containsResources) {
            return false;
        }

        if ($this->debug) {
            $cachedHash = $this->cacheProvider->fetch(self::CACHE_KEY_HASH);

            return $cachedHash === $this->hashProvider->getHash($this->getResources());
        }

        return true;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        if (!$this->configuration) {
            if ($this->isFresh()) {
                $this->configuration = unserialize($this->cacheProvider->fetch(self::CACHE_KEY_CONFIGURATION));
            } else {
                $this->configuration = $this->configurationProvider->getConfiguration();
                $this->cacheProvider->saveMultiple([
                    self::CACHE_KEY_HASH => $this->hashProvider->getHash($this->getResources()),
                    self::CACHE_KEY_CONFIGURATION => serialize($this->configuration),
                ]);
            }
        }

        return $this->configuration;
    }
}
