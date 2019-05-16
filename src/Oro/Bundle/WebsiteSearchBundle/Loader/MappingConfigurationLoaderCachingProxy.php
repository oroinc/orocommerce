<?php

namespace Oro\Bundle\WebsiteSearchBundle\Loader;

use Oro\Bundle\WebsiteSearchBundle\Cache\MappingConfigurationCacheProvider;
use Oro\Bundle\WebsiteSearchBundle\Provider\ResourcesHashProvider;
use Oro\Component\Config\CumulativeResourceInfo;

/**
 * The website search configuration loader which adds caching for mapping configuration.
 */
class MappingConfigurationLoaderCachingProxy implements ConfigurationLoaderInterface
{
    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var MappingConfigurationCacheProvider
     */
    protected $mappingCacheProvider;

    /**
     * @var ConfigurationLoaderInterface
     */
    protected $configurationProvider;

    /**
     * @var string
     */
    protected $hash;

    /**
     * @var CumulativeResourceInfo[]
     */
    protected $resources;

    /**
     * @var ResourcesHashProvider
     */
    protected $hashProvider;

    /**
     * @param ConfigurationLoaderInterface $configurationProvider
     * @param ResourcesHashProvider $hashProvider
     * @param bool $debug
     */
    public function __construct(
        ConfigurationLoaderInterface $configurationProvider,
        ResourcesHashProvider $hashProvider,
        $debug
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->hashProvider = $hashProvider;
        $this->debug = (bool)$debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        [$hash, $configuration] = $this->getMappingConfigurationCacheProvider()->fetchConfiguration();

        // If cache hasn't been cleared and it's not the first invocation of getConfiguration
        // then return cached configuration
        if ($this->hash === $hash) {
            return $configuration;
        }

        if ($this->isFresh($hash)) {
            $this->hash = $hash;

            return $configuration;
        }

        $this->hash = $this->hashProvider->getHash($this->getResources());
        $configuration = $this->configurationProvider->getConfiguration();
        $this->getMappingConfigurationCacheProvider()->saveConfiguration($this->hash, $configuration);

        return $configuration;
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
     * @param MappingConfigurationCacheProvider $cacheProvider
     */
    public function setMappingConfigurationCacheProvider(MappingConfigurationCacheProvider $cacheProvider): void
    {
        $this->mappingCacheProvider = $cacheProvider;
    }

    /**
     * @return bool
     */
    public function isCachingProxyFullyConfigured(): bool
    {
        return $this->configurationProvider && $this->hashProvider && $this->mappingCacheProvider;
    }

    public function warmUpCache()
    {
        $this->clearCache();
        $this->getConfiguration();
    }

    public function clearCache()
    {
        $this->mappingCacheProvider->deleteConfiguration();
    }

    /**
     * @return MappingConfigurationCacheProvider
     */
    protected function getMappingConfigurationCacheProvider(): MappingConfigurationCacheProvider
    {
        if (!$this->mappingCacheProvider) {
            throw new \InvalidArgumentException(sprintf('No mapping cache provider set for "%s"', __CLASS__));
        }

        return $this->mappingCacheProvider;
    }

    /**
     * @param string|bool $cachedHash
     * @return bool
     */
    private function isFresh($cachedHash): bool
    {
        if (false === $cachedHash) {
            return false;
        }

        if (!$this->debug) {
            return true;
        }

        return $cachedHash === $this->hashProvider->getHash($this->getResources());
    }
}
