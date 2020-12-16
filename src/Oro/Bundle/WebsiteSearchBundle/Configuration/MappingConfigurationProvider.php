<?php

namespace Oro\Bundle\WebsiteSearchBundle\Configuration;

use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProviderAbstract;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * The provider for website search mapping configuration
 * that is loaded from "Resources/config/oro/website_search.yml" files.
 */
class MappingConfigurationProvider extends MappingConfigurationProviderAbstract
{
    private const CONFIG_FILE = 'Resources/config/oro/website_search.yml';

    /** @var ConfigurationInterface */
    private ConfigurationInterface $configurationTree;

    /**
     * @param ConfigurationInterface $configurationTree
     * @param string $cacheFile
     * @param bool $debug
     */
    public function __construct(ConfigurationInterface $configurationTree, string $cacheFile, bool $debug)
    {
        parent::__construct($cacheFile, $debug);

        $this->configurationTree = $configurationTree;
    }

    /**
     * Gets website search mapping configuration.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_website_search',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            $configs[] = $resource->data;
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            $this->configurationTree,
            $configs
        );
    }
}
