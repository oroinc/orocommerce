<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\MappingConfiguration;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class ResourcesBasedMappingConfigurationProvider implements ResourcesBasedConfigurationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        $ymlLoader = new YamlCumulativeFileLoader('Resources/config/website_search.yml');
        $configurationLoader = new CumulativeConfigLoader('oro_website_search', $ymlLoader);

        return $configurationLoader->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $mappingConfigs = [];
        foreach ($this->getResources() as $resource) {
            foreach ($resource->data as $key => $value) {
                if (!isset($value['fields'])) {
                    $value['fields'] = [];
                }

                if (!isset($mappingConfigs[$key])) {
                    $mappingConfigs[$key] = $value;
                } else {
                    $value['fields'] = array_merge($mappingConfigs[$key]['fields'], $value['fields']);
                    $mappingConfigs[$key] = array_merge($mappingConfigs[$key], $value);
                }
            }
        }

        $mappings = $this->processConfiguration(new MappingConfiguration(), [['mappings' => $mappingConfigs]]);

        return $mappings['mappings'];
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
