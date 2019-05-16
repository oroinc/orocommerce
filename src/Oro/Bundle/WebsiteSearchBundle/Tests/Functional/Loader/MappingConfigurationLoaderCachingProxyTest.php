<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Loader;

use Oro\Bundle\FrontendTestFrameworkBundle\EventListener\WebsiteSearchMappingListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;

/**
 * @dbIsolationPerTest
 */
class MappingConfigurationLoaderCachingProxyTest extends WebTestCase
{
    /**
     * @var WebsiteSearchMappingListener
     */
    private $listener;

    /**
     * @var ConfigurationLoaderInterface
     */
    private $loader;

    protected function setUp()
    {
        $this->initClient();

        $this->loader = self::getContainer()->get('oro_website_search.alias.loader.mapping_configuration_cache_loader');

        $this->listener = self::getContainer()->get('oro_website_search.event_listener.search_mapping_provider');
        $this->listener->setEnabled();
    }

    public function testGetConfigurationLocalCache(): void
    {
        $this->clearCache();

        $this->loader->getConfiguration();

        self::assertNotEmpty($this->listener->getTriggeredEvents());

        $this->listener->clearTriggeredEvents();

        $this->loader->getConfiguration();

        self::assertEmpty($this->listener->getTriggeredEvents());
    }

    /**
     * @depends testGetConfigurationLocalCache
     */
    public function testGetConfigurationPersistentCache(): void
    {
        $this->loader->getConfiguration();

        self::assertEmpty($this->listener->getTriggeredEvents());
    }

    private function clearCache(): void
    {
        self::getContainer()
            ->get('oro_website_search.alias.cache.mapping_configuration_cache_provider')
            ->deleteConfiguration();
    }
}
