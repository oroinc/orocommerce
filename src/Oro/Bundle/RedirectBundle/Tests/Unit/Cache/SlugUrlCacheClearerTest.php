<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Oro\Bundle\RedirectBundle\Cache\SlugUrlCacheClearer;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\UrlCacheAllCapabilities;

class SlugUrlCacheClearerTest extends \PHPUnit\Framework\TestCase
{
    public function testClearClearableCache(): void
    {
        $cache = $this->createMock(UrlCacheAllCapabilities::class);
        $cache->expects($this->once())
            ->method('deleteAll');

        $cacheClearer = new SlugUrlCacheClearer($cache);
        $cacheClearer->clear(__DIR__);
    }

    public function testClearNonClearableCache()
    {
        $cache = $this->createMock(UrlCacheInterface::class);
        $cache->expects($this->never())
            ->method($this->anything());

        $cacheClearer = new SlugUrlCacheClearer($cache);
        $cacheClearer->clear(__DIR__);
    }
}
