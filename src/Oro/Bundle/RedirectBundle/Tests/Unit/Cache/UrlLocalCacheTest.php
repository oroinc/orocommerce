<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Oro\Bundle\RedirectBundle\Cache\UrlLocalCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class UrlLocalCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UrlLocalCache
     */
    private $urlCache;

    /**
     * @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
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
            ->method('hasItem')
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

        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn($data !== null);
        $item->expects($this->any())
            ->method('get')
            ->willReturn($data);
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($item);

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

        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn($data !== null);
        $item->expects($this->any())
            ->method('get')
            ->willReturn($data);
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($item);

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

        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->any())
            ->method('set')
            ->with([UrlLocalCache::URL_KEY => $url, UrlLocalCache::SLUG_KEY => $slug]);
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($item);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($item);

        $this->urlCache->setUrl($routeName, $routeParameters, $url, $slug, $localization);
    }

    public function testRemoveUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $key = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1';

        $this->cache->expects($this->once())
            ->method('deleteItem')
            ->with($key);

        $this->urlCache->removeUrl($routeName, $routeParameters, $localization);
    }

    public function testDeleteAll()
    {
        $this->cache->expects($this->once())
            ->method('clear');

        $this->urlCache->deleteAll();
    }
}
