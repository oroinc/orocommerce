<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\RedirectBundle\Cache\UrlLocalCache;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\CacheAllCapabilities;

class UrlLocalCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UrlLocalCache
     */
    private $urlCache;

    /**
     * @var Cache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);
        $this->urlCache = new UrlLocalCache($this->cache);
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $expected
     */
    public function testHas($expected)
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $key = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1';

        $this->cache->expects($this->once())
            ->method('contains')
            ->with($key)
            ->willReturn($expected);
        $this->assertSame($expected, $this->urlCache->has($routeName, $routeParameters, $localization));
    }

    /**
     * @return array
     */
    public function trueFalseDataProvider()
    {
        return [
            'true' => [true],
            'false' => [false]
        ];
    }

    /**
     * @dataProvider urlDataProvider
     * @param array|null $data
     * @param string|bool $expected
     */
    public function testGetUrl($data, $expected)
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $key = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1';

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($data);

        $this->assertSame($expected, $this->urlCache->getUrl($routeName, $routeParameters, $localization));
    }

    /**
     * @return array
     */
    public function urlDataProvider()
    {
        return [
            'has in cache' => [[UrlLocalCache::URL_KEY => '/test', UrlLocalCache::SLUG_KEY => 'test'], '/test'],
            'has in cache NULL' => [[UrlLocalCache::URL_KEY => null, UrlLocalCache::SLUG_KEY => null], null],
            'does not contain' => [null, false]
        ];
    }

    /**
     * @dataProvider slugDataProvider
     * @param array|null $data
     * @param string|bool $expected
     */
    public function testGetSlug($data, $expected)
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $key = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1';

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->willReturn($data);

        $this->assertSame($expected, $this->urlCache->getSlug($routeName, $routeParameters, $localization));
    }

    /**
     * @return array
     */
    public function slugDataProvider()
    {
        return [
            'has in cache' => [[UrlLocalCache::URL_KEY => '/test', UrlLocalCache::SLUG_KEY => 'test'], 'test'],
            'does not contain' => [null, false]
        ];
    }

    public function testSetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $key = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1';

        $url = '/test';
        $slug = 'test';
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $key,
                [UrlLocalCache::URL_KEY => $url, UrlLocalCache::SLUG_KEY => $slug]
            );

        $this->urlCache->setUrl($routeName, $routeParameters, $url, $slug, $localization);
    }

    public function testRemoveUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $key = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1';

        $this->cache->expects($this->once())
            ->method('delete')
            ->with($key);

        $this->urlCache->removeUrl($routeName, $routeParameters, $localization);
    }

    public function testDeleteAllDefault()
    {
        $this->cache->expects($this->never())
            ->method($this->anything());
        $this->urlCache->deleteAll();
    }

    public function testDeleteAllClearable()
    {
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(CacheAllCapabilities::class);
        $cache->expects($this->once())
            ->method('deleteAll');

        $urlCache = new UrlLocalCache($cache);
        $urlCache->deleteAll();
    }
}
