<?php

namespace Oro\Bundle\WebsiteSearchBundle\Configuration;

use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The provider for website search mapping configuration
 * that is loaded from "Resources/config/oro/website_search.yml" files.
 */
class MappingConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/website_search.yml';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param string $cacheFile
     * @param bool $debug
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(string $cacheFile, bool $debug, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($cacheFile, $debug);

        $this->eventDispatcher = $eventDispatcher;
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

        $event = new WebsiteSearchMappingEvent();
        $this->eventDispatcher->dispatch(WebsiteSearchMappingEvent::NAME, $event);
        $configs[] = $event->getConfiguration();

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new MappingConfiguration(),
            $configs
        );
    }
}
