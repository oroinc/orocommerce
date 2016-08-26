<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Cache;

use Oro\Bundle\WebsiteSearchBundle\Cache\ConfigurationCacheClearer;
use Oro\Bundle\WebsiteSearchBundle\Provider\ConfigurationCacheProvider;

class ConfigurationCacheClearerTest extends \PHPUnit_Framework_TestCase
{
    public function testClear()
    {
        /** @var ConfigurationCacheProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this
            ->getMockBuilder(ConfigurationCacheProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider
            ->expects($this->once())
            ->method('clearCache');

        $cacheClearer = new ConfigurationCacheClearer($provider);
        $cacheClearer->clear('');
    }
}
