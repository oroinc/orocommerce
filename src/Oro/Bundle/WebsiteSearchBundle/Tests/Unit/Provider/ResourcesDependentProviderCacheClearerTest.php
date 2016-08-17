<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\WebsiteSearchBundle\Cache\ResourcesDependentProviderCacheClearer;
use Doctrine\Common\Cache\CacheProvider;

class ResourcesDependentProviderCacheClearerTest extends \PHPUnit_Framework_TestCase
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

        $cacheClearer = new ResourcesDependentProviderCacheClearer($cacheProvider);
        $cacheClearer->clear('');
    }
}
