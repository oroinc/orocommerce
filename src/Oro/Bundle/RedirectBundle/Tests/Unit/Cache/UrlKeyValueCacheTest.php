<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Oro\Bundle\RedirectBundle\Cache\UrlKeyValueCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UrlKeyValueCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $persistentCache;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $persistentCacheItem;

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localCache;

    /** @var UrlKeyValueCache */
    private $urlCache;

    protected function setUp(): void
    {
        $this->persistentCache = $this->createMock(CacheItemPoolInterface::class);
        $this->localCache = $this->createMock(CacheItemPoolInterface::class);
        $this->persistentCacheItem = $this->createMock(CacheItemInterface::class);

        $this->urlCache = new UrlKeyValueCache($this->persistentCache, $this->localCache);
    }

    /**
     * @dataProvider hasDataProvider
     */
    public function testHas(bool $hasInLocal, bool $hasInPersistent, bool $expected)
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $this->localCache->expects($this->any())
            ->method('hasItem')
            ->with($keyLocalization)
            ->willReturn($hasInLocal);

        $this->persistentCache->expects($this->any())
            ->method('hasItem')
            ->with($keyLocalization)
            ->willReturn($hasInPersistent);

        $this->assertSame($expected, $this->urlCache->has($routeName, $routeParameters, $localization));
    }

    public function hasDataProvider(): array
    {
        return [
            'do not contains' => [false, false, false],
            'has in local no in persistent' => [true, false, true],
            'has in persistent no in local' => [false, true, true],
            'has in both' => [true, true, true],
        ];
    }

    public function testGetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $urlItem = $this->createMock(CacheItemInterface::class);
        $urlItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $urlItem->expects($this->once())
            ->method('get')
            ->willReturn($url);
        $this->localCache->expects($this->once())
            ->method('getItem')
            ->with($keyLocalization)
            ->willReturn($urlItem);

        $this->assertSame($url, $this->urlCache->getUrl($routeName, $routeParameters, $localization));
    }

    public function testGetUrlNullNotInCacheYet()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $this->persistentCache->expects($this->once())
            ->method('getItem')
            ->with($keyLocalization)
            ->willReturn($this->persistentCacheItem);
        $this->persistentCacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $this->persistentCacheItem->expects($this->exactly(2))
            ->method('get')
            ->willReturn($url);

        $urlItem = $this->createMock(CacheItemInterface::class);
        $urlItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $this->localCache->expects($this->once())
            ->method('getItem')
            ->with($keyLocalization)
            ->willReturn($urlItem);
        $this->localCache->expects($this->once())
            ->method('save')
            ->with($urlItem);

        $this->assertSame($url, $this->urlCache->getUrl($routeName, $routeParameters, $localization));
    }

    public function testGetUrlNotContainsInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $this->persistentCache->expects($this->once())
            ->method('getItem')
            ->with($keyLocalization)
            ->willReturn($this->persistentCacheItem);
        $this->persistentCacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $urlItem = $this->createMock(CacheItemInterface::class);
        $urlItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $urlItem->expects($this->never())
            ->method('get');
        $this->localCache->expects($this->once())
            ->method('getItem')
            ->with($keyLocalization)
            ->willReturn($urlItem);
        $this->localCache->expects($this->never())
            ->method('save');

        $this->assertFalse($this->urlCache->getUrl($routeName, $routeParameters, $localization));
    }

    public function testGetSlug()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $slug = 'test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_s';

        $urlItem = $this->createMock(CacheItemInterface::class);
        $urlItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $urlItem->expects($this->once())
            ->method('get')
            ->willReturn($slug);
        $this->localCache->expects($this->once())
            ->method('getItem')
            ->with($keyLocalization)
            ->willReturn($urlItem);

        $this->assertSame($slug, $this->urlCache->getSlug($routeName, $routeParameters, $localization));
    }

    public function testSetUrlWithSlug()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $slug = 'test';
        $keyUrlLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';
        $keySlugLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_s';

        $urlItem = $this->createMock(CacheItemInterface::class);
        $urlItem->expects($this->once())
            ->method('set')
            ->with($url);
        $slugItem = $this->createMock(CacheItemInterface::class);
        $slugItem->expects($this->once())
            ->method('set')
            ->with($slug);
        $this->localCache->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive(
                [$keyUrlLocalization],
                [$keySlugLocalization]
            )
            ->willReturnOnConsecutiveCalls(
                $urlItem,
                $slugItem
            );

        $this->localCache->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [$urlItem],
                [$slugItem]
            );

        $this->urlCache->setUrl($routeName, $routeParameters, $url, $slug, $localization);
    }

    public function testSetUrlWithoutSlug()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $slug = null;
        $keyUrlLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $urlItem = $this->createMock(CacheItemInterface::class);
        $urlItem->expects($this->once())
            ->method('set')
            ->with($url);
        $this->localCache->expects($this->once())
            ->method('getItem')
            ->with($keyUrlLocalization)
            ->willReturn($urlItem);

        $this->localCache->expects($this->once())
            ->method('save')
            ->with($urlItem);

        $this->urlCache->setUrl($routeName, $routeParameters, $url, $slug, $localization);
    }

    public function testRemoveUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $keyUrlLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';
        $keySlugLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_s';

        $this->localCache->expects($this->exactly(2))
            ->method('deleteItem')
            ->withConsecutive(
                [$keyUrlLocalization],
                [$keySlugLocalization]
            );
        $this->persistentCache->expects($this->exactly(2))
            ->method('deleteItem')
            ->withConsecutive(
                [$keyUrlLocalization],
                [$keySlugLocalization]
            );

        $this->urlCache->removeUrl($routeName, $routeParameters, $localization);
    }

    public function testDeleteAllPersistentClearable()
    {
        $this->localCache->expects($this->once())
            ->method('clear');
        $this->persistentCache->expects($this->once())
            ->method('clear');

        $urlCache = new UrlKeyValueCache($this->persistentCache, $this->localCache);
        $urlCache->deleteAll();
    }

    public function testFlushAllMulti()
    {
        $localCache = new ArrayAdapter();
        $urlCache = new UrlKeyValueCache($this->persistentCache, $localCache);
        $urlCache->setUrl('test_1', [], '/test', 'test', 1);
        $urlCache->setUrl('test', ['id' => 1], '/my-test', null);

        $this->persistentCache->expects($this->exactly(5))
            ->method('getItem')
            ->withConsecutive(
                ['test_1_YTowOnt9_1_u'],
                ['test_1_YTowOnt9_1_s'],
                ['test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u'],
                ['test_1_YTowOnt9_2_u'],
                ['test_1_YTowOnt9_2_s'],
            )->willReturn($this->persistentCacheItem);
        $this->persistentCacheItem->expects($this->exactly(5))
            ->method('set')
            ->withConsecutive(
                ['/test'],
                ['test'],
                ['/my-test'],
                ['/test2'],
                ['test2'],
            );
        $this->persistentCache->expects($this->exactly(5))
            ->method('saveDeferred')
            ->with($this->persistentCacheItem);
        $this->persistentCache->expects($this->exactly(2))
            ->method('commit');

        $this->assertNotEmpty($localCache->getValues());
        $urlCache->flushAll();
        $this->assertEmpty($localCache->getValues());

        // Check that second call of flushAll is not dependent on data from the first call
        $urlCache->setUrl('test_1', [], '/test2', 'test2', 2);
        $urlCache->flushAll();
    }

    public function testFlushAllMultiNoChanges()
    {
        $urlCache = new UrlKeyValueCache($this->persistentCache, $this->localCache);

        $this->localCache->expects($this->once())
            ->method('getItems')
            ->with([])
            ->willReturn([]);
        $this->persistentCache->expects($this->never())
            ->method('saveDeferred');

        $urlCache->flushAll();
    }

    public function testFlushAllNonMulti()
    {
        $localCache = new ArrayAdapter();
        $urlCache = new UrlKeyValueCache($this->persistentCache, $localCache);
        $urlCache->setUrl('test_1', [], '/test', 'test', 1);
        $urlCache->setUrl('test', ['id' => 1], '/my-test', null);

        $this->persistentCache->expects($this->exactly(3))
            ->method('getItem')
            ->withConsecutive(
                ['test_1_YTowOnt9_1_u'],
                ['test_1_YTowOnt9_1_s'],
                ['test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u'],
            )->willReturn($this->persistentCacheItem);
        $this->persistentCacheItem->expects($this->exactly(3))
            ->method('set')
            ->withConsecutive(
                ['/test'],
                ['test'],
                ['/my-test'],
            );
        $this->persistentCache->expects($this->exactly(3))
            ->method('saveDeferred')
            ->with($this->persistentCacheItem);
        $this->persistentCache->expects($this->once())
            ->method('commit');

        $urlCache->flushAll();
    }
}
