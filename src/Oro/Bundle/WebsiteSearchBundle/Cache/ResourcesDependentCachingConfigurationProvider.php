<?php

namespace Oro\Bundle\WebsiteSearchBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesBasedConfigurationProviderInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ResourcesDependentCachingConfigurationProvider
{
    const TIME_INCREMENT = 1;
    const CACHE_KEY_RESOURCES = 'cache_key_resources';
    const CACHE_KEY_LAST_MODIFICATION_TIME = 'cache_key_last_modification_time';
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
     * @var ResourcesBasedConfigurationProviderInterface
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
     * @param CacheProvider $cacheProvider
     * @param bool $debug
     * @param ResourcesBasedConfigurationProviderInterface $configurationProvider
     */
    public function __construct(
        CacheProvider $cacheProvider,
        $debug,
        ResourcesBasedConfigurationProviderInterface $configurationProvider
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->cacheProvider = $cacheProvider;
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
        $containsResources = $this->cacheProvider->contains(self::CACHE_KEY_RESOURCES);
        if (!$containsResources) {
            return false;
        }

        if ($this->debug) {
            $storedResources = unserialize($this->cacheProvider->fetch(self::CACHE_KEY_RESOURCES));

            if (!$this->resourcesAreSame($storedResources)) {
                return false;
            }

            $lastModificationTime = $this->cacheProvider->fetch(self::CACHE_KEY_LAST_MODIFICATION_TIME);
            foreach ($storedResources as $resource) {
                if (filemtime($resource->path) > $lastModificationTime) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param CumulativeResourceInfo[] $storedResources
     * @return bool
     */
    protected function resourcesAreSame($storedResources)
    {
        $getPathCallback = function (CumulativeResourceInfo $resource) {
            return $resource->path;
        };

        $resourcesPaths = array_map($getPathCallback, $this->getResources());
        $storedResourcesPaths = array_map($getPathCallback, $storedResources);

        sort($resourcesPaths);
        sort($storedResourcesPaths);

        return $resourcesPaths === $storedResourcesPaths;
    }

    /**
     * @return int
     */
    protected function getResourcesMaxModificationTime()
    {
        $lastModificationTime = 0;

        foreach ($this->getResources() as $resource) {
            $lastModificationTime = max($lastModificationTime, filemtime($resource->path));
        }

        return $lastModificationTime;
    }

    /**
     * @return array
     */
    public function getConfigurationData()
    {
        if (!$this->configuration) {
            if ($this->isFresh()) {
                $this->configuration = unserialize($this->cacheProvider->fetch(self::CACHE_KEY_CONFIGURATION));
            } else {
                $this->configuration = $this->configurationProvider->getConfiguration();
                $this->cacheProvider->saveMultiple([
                    self::CACHE_KEY_RESOURCES => serialize($this->getResources()),
                    self::CACHE_KEY_LAST_MODIFICATION_TIME =>
                        $this->getResourcesMaxModificationTime() + self::TIME_INCREMENT,
                    self::CACHE_KEY_CONFIGURATION => serialize($this->configuration),
                ]);
            }
        }

        return $this->configuration;
    }
}
