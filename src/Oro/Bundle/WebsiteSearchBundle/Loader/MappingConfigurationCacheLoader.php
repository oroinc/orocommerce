<?php

namespace Oro\Bundle\WebsiteSearchBundle\Loader;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Component\Config\CumulativeResourceInfo;

/**
 * The website search configuration loader.
 */
class MappingConfigurationCacheLoader extends MappingConfigurationLoaderCachingProxy
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
     * @param ResourcesHashProvider $hashProvider
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
        $this->debug = (bool)$debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->isCachingProxyFullyConfigured()) {
            return parent::getConfiguration();
        }

        if (null !== $this->configuration) {
            return $this->configuration;
        }

        $this->warmUpConfiguration();

        return $this->configuration;
    }

    protected function warmUpConfiguration()
    {
        if ($this->isFresh()) {
            $this->configuration = $this->cacheProvider->fetch(self::CACHE_KEY_CONFIGURATION);

            return;
        }

        $this->configuration = $this->configurationProvider->getConfiguration();
        $this->cacheProvider->saveMultiple([
            self::CACHE_KEY_HASH => $this->hashProvider->getHash($this->getResources()),
            self::CACHE_KEY_CONFIGURATION => $this->configuration,
        ]);
    }

    public function warmUpCache()
    {
        if ($this->isCachingProxyFullyConfigured()) {
            return parent::warmUpCache();
        }
        $this->clearCache();
        $this->warmUpConfiguration();
    }

    public function clearCache()
    {
        if ($this->isCachingProxyFullyConfigured()) {
            return parent::clearCache();
        }

        $this->cacheProvider->delete(self::CACHE_KEY_HASH);
        $this->cacheProvider->delete(self::CACHE_KEY_CONFIGURATION);
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        if ($this->isCachingProxyFullyConfigured()) {
            return parent::getResources();
        }

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
        $cachedHash = $this->cacheProvider->fetch(self::CACHE_KEY_HASH);
        if (false === $cachedHash) {
            return false;
        }

        if (!$this->debug) {
            return true;
        }

        return $cachedHash === $this->hashProvider->getHash($this->getResources());
    }
}
