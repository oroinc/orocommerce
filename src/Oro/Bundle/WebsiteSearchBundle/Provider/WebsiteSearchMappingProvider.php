<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides mapping config for website search
 */
class WebsiteSearchMappingProvider extends AbstractSearchMappingProvider
{
    /** @var ConfigurationLoaderInterface */
    private $mappingConfigurationLoader;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param ConfigurationLoaderInterface $mappingConfigurationLoader
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigurationLoaderInterface $mappingConfigurationLoader,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->mappingConfigurationLoader = $mappingConfigurationLoader;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        return $this->mappingConfigurationLoader->getConfiguration();
    }

    /**
     * Invalidate local cache
     */
    public function clearCache()
    {
    }
}
