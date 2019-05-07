<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;

/**
 * The provider for website search mappings.
 */
class WebsiteSearchMappingProvider extends AbstractSearchMappingProvider
{
    /** @var MappingConfigurationProvider */
    private $mappingConfigurationProvider;

    /**
     * @param MappingConfigurationProvider $mappingConfigurationProvider
     */
    public function __construct(MappingConfigurationProvider $mappingConfigurationProvider)
    {
        $this->mappingConfigurationProvider = $mappingConfigurationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        return $this->mappingConfigurationProvider->getConfiguration();
    }
}
