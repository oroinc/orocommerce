<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Cache;

use Oro\Bundle\WebsiteSearchBundle\Cache\ConfigurationCacheWarmer;
use Oro\Bundle\WebsiteSearchBundle\Provider\ConfigurationCacheProvider;

class ConfigurationCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    public function testWarmUpCache()
    {
        /** @var ConfigurationCacheProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this
            ->getMockBuilder(ConfigurationCacheProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider
            ->expects($this->once())
            ->method('warmUpCache');

        $cacheWarmer = new ConfigurationCacheWarmer($provider);
        $cacheWarmer->warmUp('');
    }
}
