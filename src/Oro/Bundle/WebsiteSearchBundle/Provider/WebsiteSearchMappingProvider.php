<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfiguration;
use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The provider for website search mappings.
 */
class WebsiteSearchMappingProvider extends AbstractSearchMappingProvider implements
    WarmableConfigCacheInterface,
    ClearableConfigCacheInterface
{
    private const CACHE_KEY = 'oro_website_search.mapping_config';

    /** @var MappingConfigurationProvider */
    private $mappingConfigurationProvider;

    /** @var EventDispatcherInterface $dispatcher , */
    private $dispatcher;

    /** @var Cache */
    private $cache;

    /** @var array|null */
    private $configuration;

    /**
     * @param MappingConfigurationProvider $mappingConfigurationProvider
     * @param EventDispatcherInterface     $dispatcher
     * @param Cache                        $cache
     */
    public function __construct(
        MappingConfigurationProvider $mappingConfigurationProvider,
        EventDispatcherInterface $dispatcher,
        Cache $cache
    ) {
        $this->mappingConfigurationProvider = $mappingConfigurationProvider;
        $this->dispatcher = $dispatcher;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        if (null === $this->configuration) {
            $config = $this->fetchMappingConfigFromCache();
            if (null === $config) {
                $config = $this->loadMappingConfig();
                $this->saveMappingConfigToCache($config);
            }
            $this->configuration = $config;
        }

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->configuration = null;
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->configuration = null;
        $this->cache->delete(self::CACHE_KEY);
        $this->getMappingConfig();
    }

    /**
     * @return array|null
     */
    private function fetchMappingConfigFromCache(): ?array
    {
        $config = null;
        $cachedData = $this->cache->fetch(self::CACHE_KEY);
        if (false !== $cachedData) {
            [$timestamp, $value] = $cachedData;
            if ($this->mappingConfigurationProvider->isCacheFresh($timestamp)) {
                $config = $value;
            }
        }

        return $config;
    }

    /**
     * @param array $config
     */
    private function saveMappingConfigToCache(array $config): void
    {
        $this->cache->save(self::CACHE_KEY, [$this->mappingConfigurationProvider->getCacheTimestamp(), $config]);
    }

    /**
     * @return array
     */
    private function loadMappingConfig(): array
    {
        $event = new WebsiteSearchMappingEvent();
        $this->dispatcher->dispatch($event, WebsiteSearchMappingEvent::NAME);

        $processor = new Processor();
        return $processor->processConfiguration(
            new MappingConfiguration(),
            [$this->mappingConfigurationProvider->getConfiguration(), $event->getConfiguration()]
        );
    }
}
