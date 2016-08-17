<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Cache;

use Oro\Bundle\WebsiteSearchBundle\Cache\ConfigurationCacheClearer;
use Doctrine\Common\Cache\CacheProvider;

class ConfigurationCacheClearerTest extends \PHPUnit_Framework_TestCase
{
    public function testClear()
    {
        /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject $cacheProvider */
        $cacheProvider = $this
            ->getMockBuilder(CacheProvider::class)
            ->getMock();

        $cacheProvider
            ->expects($this->once())
            ->method('deleteAll');

        $cacheClearer = new ConfigurationCacheClearer($cacheProvider);
        $cacheClearer->clear('');
    }
}
