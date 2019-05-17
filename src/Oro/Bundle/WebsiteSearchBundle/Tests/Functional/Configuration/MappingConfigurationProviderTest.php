<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Configuration;

use Oro\Bundle\FrontendTestFrameworkBundle\EventListener\WebsiteSearchMappingListener;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;

/**
 * @dbIsolationPerTest
 */
class MappingConfigurationProviderTest extends WebTestCase
{
    /**
     * @var WebsiteSearchMappingListener
     */
    private $listener;

    /**
     * @var MappingConfigurationProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->initClient();

        $this->provider = self::getContainer()->get('oro_website_search.alias.mapping_configuration.provider');

        $this->listener = self::getContainer()->get('oro_website_search.event_listener.search_mapping_provider');
        $this->listener->setEnabled();
    }

    public function testGetConfigurationLocalCache(): void
    {
        $this->provider->clearCache();

        $this->provider->getConfiguration();

        self::assertNotEmpty($this->listener->getTriggeredEvents());

        $this->listener->clearTriggeredEvents();

        $this->provider->getConfiguration();

        self::assertEmpty($this->listener->getTriggeredEvents());
    }

    /**
     * @depends testGetConfigurationLocalCache
     */
    public function testGetConfigurationPersistentCache(): void
    {
        $this->provider->getConfiguration();

        self::assertEmpty($this->listener->getTriggeredEvents());
    }
}
