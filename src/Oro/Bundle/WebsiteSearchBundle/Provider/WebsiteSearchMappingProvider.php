<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;

class WebsiteSearchMappingProvider extends AbstractSearchMappingProvider
{
    /**
     * @var ConfigurationLoaderInterface
     */
    private $mappingConfigurationLoader;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param ConfigurationLoaderInterface $mappingConfigurationLoader
     */
    public function __construct(ConfigurationLoaderInterface $mappingConfigurationLoader)
    {
        $this->mappingConfigurationLoader = $mappingConfigurationLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        if (!$this->configuration) {
            $this->configuration = $this->mappingConfigurationLoader->getConfiguration();
        }

        return $this->configuration;
    }
}
