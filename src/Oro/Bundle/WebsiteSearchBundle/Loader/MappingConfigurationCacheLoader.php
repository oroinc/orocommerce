<?php

namespace Oro\Bundle\WebsiteSearchBundle\Loader;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;

class MappingConfigurationCacheLoader implements ConfigurationLoaderInterface
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
     * @param bool $debug
     */
    public function __construct(
        CacheProvider $cacheProvider,
        ConfigurationLoaderInterface $configurationProvider,
        $debug
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->cacheProvider = $cacheProvider;
        $this->debug = (bool)$debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
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
            self::CACHE_KEY_HASH => $this->getHash($this->getResources()),
            self::CACHE_KEY_CONFIGURATION => $this->configuration,
        ]);
    }

    public function warmUpCache()
    {
        $this->clearCache();
        $this->warmUpConfiguration();
    }

    public function clearCache()
    {
        $this->cacheProvider->delete(self::CACHE_KEY_HASH);
        $this->cacheProvider->delete(self::CACHE_KEY_CONFIGURATION);
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        if (null === $this->resources) {
            $this->resources = $this->configurationProvider->getResources();
        }

        return $this->resources;
    }

    /**
     * @param ResourcesHashProvider $hashProvider
     */
    public function setHashProvider(ResourcesHashProvider $hashProvider)
    {
        $this->hashProvider = $hashProvider;
    }

    /**
     * @return bool
     */
    protected function isFresh()
    {
        $cacheExists = $this->cacheProvider->contains(self::CACHE_KEY_HASH);

        if (!$cacheExists) {
            return false;
        }

        if (!$this->debug) {
            return true;
        }

        $cachedHash = $this->cacheProvider->fetch(self::CACHE_KEY_HASH);

        return $cachedHash === $this->getHash($this->getResources());
    }

    /**
     * @param CumulativeResourceInfo[] $resources
     * @return string
     */
    protected function getHash(array $resources)
    {
        return $this->getHashProvider()->getHash($resources);
    }

    /**
     * @return ResourcesHashProvider
     */
    protected function getHashProvider()
    {
        if (!$this->hashProvider) {
            $this->hashProvider = new ResourcesHashProvider();
        }

        return $this->hashProvider;
    }
}
