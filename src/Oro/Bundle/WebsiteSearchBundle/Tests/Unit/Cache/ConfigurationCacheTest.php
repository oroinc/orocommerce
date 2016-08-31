<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Cache;

use Oro\Bundle\WebsiteSearchBundle\Cache\ConfigurationCache;
use Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationCachedLoader;

class ConfigurationCacheTest extends \PHPUnit_Framework_TestCase
{
    /** @var MappingConfigurationCachedLoader|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationCacheLoader;

    /** @var ConfigurationCache */
    protected $configurationCache;

    protected function setUp()
    {
        $this->configurationCacheLoader = $this
            ->getMockBuilder(MappingConfigurationCachedLoader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationCache = new ConfigurationCache($this->configurationCacheLoader);
    }

    protected function tearDown()
    {
        unset($this->configurationCacheLoader, $this->configurationCache);
    }

    public function testClear()
    {
        $this->configurationCacheLoader
            ->expects($this->once())
            ->method('clearCache');

        $this->configurationCache->clear('');
    }

    public function testWarmUpCache()
    {
        $this->configurationCacheLoader
            ->expects($this->once())
            ->method('warmUpCache');

        $this->configurationCache->warmUp('');
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->configurationCache->isOptional());
    }
}
