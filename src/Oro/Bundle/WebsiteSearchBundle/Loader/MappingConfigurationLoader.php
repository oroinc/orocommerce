<?php

namespace Oro\Bundle\WebsiteSearchBundle\Loader;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\MappingConfiguration;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Loads website search mapping configuration based on configuration defined in website_search.yml files and allows to
 * also add configuration by listening to WebsiteSearchMappingEvent.
 */
class MappingConfigurationLoader implements ConfigurationLoaderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        $ymlLoader = new YamlCumulativeFileLoader('Resources/config/oro/website_search.yml');
        $configurationLoader = new CumulativeConfigLoader('oro_website_search', $ymlLoader);

        return $configurationLoader->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configs = [];
        foreach ($this->getResources() as $resource) {
            $configs[] = $resource->data;
        }

        if ($this->eventDispatcher) {
            $event = new WebsiteSearchMappingEvent();
            $this->eventDispatcher->dispatch(WebsiteSearchMappingEvent::NAME, $event);

            $configs[] = $event->getConfiguration();
        }

        return $this->processConfiguration(new MappingConfiguration(), $configs);
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ConfigurationInterface $configuration
     * @param array $configs
     * @return array
     */
    private function processConfiguration(ConfigurationInterface $configuration, array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($configuration, $configs);
    }
}
