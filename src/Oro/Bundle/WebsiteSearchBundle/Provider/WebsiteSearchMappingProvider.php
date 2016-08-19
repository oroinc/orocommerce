<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;

class WebsiteSearchMappingProvider extends AbstractSearchMappingProvider
{
    const CACHE_KEY = 'oro_website_search.mapping_config';

    /**
     * @var ConfigurationLoaderInterface
     */
    private $mappingConfigurationLoader;

    /**
     * @param ConfigurationLoaderInterface $mappingConfigurationLoader
     */
    public function setMappingConfigurationLoader(ConfigurationLoaderInterface $mappingConfigurationLoader)
    {
        $this->mappingConfigurationLoader = $mappingConfigurationLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        if (!$this->isCollected) {
            $this->isCollected = true;

            if ($this->cacheDriver->contains(static::CACHE_KEY)) {
                $this->mappingConfig = $this->cacheDriver->fetch(static::CACHE_KEY);
            } else {
                $this->mappingConfig = $this->mappingConfigurationLoader->getConfiguration();
                $this->cacheDriver->save(static::CACHE_KEY, $this->mappingConfig);
            }
        }

        return $this->mappingConfig;
    }
}
