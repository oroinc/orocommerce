<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\MappingConfiguration;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The provider for website search mappings.
 */
class WebsiteSearchMappingProvider extends AbstractSearchMappingProvider
{
    /** @var MappingConfigurationProvider */
    private $mappingConfigurationProvider;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var array */
    private $configuration;

    /**
     * @param MappingConfigurationProvider $mappingConfigurationProvider
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        MappingConfigurationProvider $mappingConfigurationProvider,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->mappingConfigurationProvider = $mappingConfigurationProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        if (!$this->configuration) {
            $event = new WebsiteSearchMappingEvent();
            $event->setConfiguration($this->mappingConfigurationProvider->getConfiguration());

            $this->eventDispatcher->dispatch(WebsiteSearchMappingEvent::NAME, $event);

            $this->configuration = $this->processConfiguration(
                new MappingConfiguration(),
                [$event->getConfiguration()]
            );
        }

        return $this->configuration;
    }

    /**
     * Invalidate local cache
     */
    public function clearCache()
    {
        $this->configuration = null;
    }

    /**
     * @param ConfigurationInterface $configuration
     * @param array $configs
     *
     * @return array
     */
    private function processConfiguration(ConfigurationInterface $configuration, array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($configuration, $configs);
    }
}
