<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Oro\Bundle\RedirectBundle\Cache\UrlCacheFactory;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;

class UrlCacheFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetKnown()
    {
        /** @var UrlCacheInterface $cache */
        $cache = $this->createMock(UrlCacheInterface::class);
        $type = 'test';
        $factory = new UrlCacheFactory($type);
        $factory->registerCache($type, $cache);
        $this->assertSame($cache, $factory->get());
    }

    public function testGetUnknown()
    {
        /** @var UrlCacheInterface $cache */
        $cache = $this->createMock(UrlCacheInterface::class);
        $type = 'test';
        $factory = new UrlCacheFactory('some_type');
        $factory->registerCache($type, $cache);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There is no UrlCache registered for type some_type. Known types: test');

        $factory->get();
    }
}
