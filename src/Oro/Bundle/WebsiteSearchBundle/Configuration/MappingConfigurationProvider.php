<?php

namespace Oro\Bundle\WebsiteSearchBundle\Configuration;

use Oro\Bundle\SearchBundle\Configuration\MappingConfigurationProviderAbstract;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for website search mapping configuration
 * that is loaded from "Resources/config/oro/website_search.yml" files.
 */
class MappingConfigurationProvider extends MappingConfigurationProviderAbstract
{
    private const CONFIG_FILE = 'Resources/config/oro/website_search.yml';

    /**
     * Gets website search mapping configuration.
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
        $configLoader = CumulativeConfigLoaderFactory::create('oro_website_search', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            $configs[] = $resource->data;
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new MappingConfiguration(),
            $configs
        );
    }
}
