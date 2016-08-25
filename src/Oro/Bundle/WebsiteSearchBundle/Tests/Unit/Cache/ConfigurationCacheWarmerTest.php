<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Cache;

use Oro\Bundle\WebsiteSearchBundle\Cache\ConfigurationCacheWarmer;
use Oro\Bundle\WebsiteSearchBundle\Provider\ConfigurationCacheProvider;

class ConfigurationCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationCacheProvider|\PHPUnit_Framework_MockObject_MockObject $configurationCacheProvider
     */
    private $configurationCacheProvider;

    protected function setUp()
    {
        $this->configurationCacheProvider = $this
            ->getMockBuilder(ConfigurationCacheProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->configurationCacheProvider);
    }

    public function testWarmUpCache()
    {
        $this->configurationCacheProvider
            ->expects($this->once())
            ->method('warmUpCache');

        $cacheWarmer = new ConfigurationCacheWarmer($this->configurationCacheProvider);
        $cacheWarmer->warmUp('');
    }

    public function testIsOptional()
    {
        $cacheWarmer = new ConfigurationCacheWarmer($this->configurationCacheProvider);

        $this->assertFalse($cacheWarmer->isOptional());
    }
}
