<?php

namespace Oro\Bundle\WebsiteSearchBundle\Loader;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\SearchBundle\DependencyInjection\Merger\SearchConfigMerger;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\MappingConfiguration;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class MappingConfigurationLoader implements ConfigurationLoaderInterface
{
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

        return $this->processConfiguration(new MappingConfiguration(), $configs);
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
