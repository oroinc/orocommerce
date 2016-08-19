<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Tests\Unit\Provider\AbstractSearchMappingProviderTest;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

class WebsiteSearchMappingProviderTest extends AbstractSearchMappingProviderTest
{
    protected function setUp()
    {
        parent::setUp();

        /** @var ConfigurationLoaderInterface|\PHPUnit_Framework_MockObject_MockObject $mappingConfigurationLoader */
        $mappingConfigurationLoader = $this->getMock(
            'Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationLoader'
        );
        $mappingConfigurationLoader
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->testMapping);

        $this->cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');

        $this->provider = new WebsiteSearchMappingProvider($this->eventDispatcher, $this->cacheDriver);
        $this->provider->setMappingConfigurationLoader($mappingConfigurationLoader);
    }

    public function testGetMappingConfigCached()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('contains')
            ->with('oro_website_search.mapping_config')
            ->willReturn(true);

        $this->cacheDriver
            ->expects($this->once())
            ->method('fetch')
            ->with('oro_website_search.mapping_config')
            ->willReturn($this->testMapping);

        $this->assertEquals($this->testMapping, $this->provider->getMappingConfig());
    }

    public function testClearMappingCache()
    {
        $this->cacheDriver
            ->expects($this->once())
            ->method('delete')
            ->with('oro_website_search.mapping_config');

        $this->provider->clearMappingCache();
    }
}
