<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\RedirectBundle\Cache\UrlKeyValueCache;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\CacheAllCapabilities;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UrlKeyValueCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Cache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $persistentCache;

    /**
     * @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localCache;

    /**
     * @var UrlKeyValueCache
     */
    private $urlCache;

    protected function setUp(): void
    {
        $this->persistentCache = $this->createMock(Cache::class);
        $this->localCache = $this->createMock(CacheItemPoolInterface::class);

        $this->urlCache = new UrlKeyValueCache($this->persistentCache, $this->localCache);
    }

    /**
     * @dataProvider hasDataProvider
     * @param bool $hasInLocal
     * @param bool $hasInPersistent
     * @param bool $expected
     */
    public function testHas($hasInLocal, $hasInPersistent, $expected)
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
            ->method('contains')
            ->with($keyLocalization)
            ->willReturn($hasInPersistent);

        $this->assertSame($expected, $this->urlCache->has($routeName, $routeParameters, $localization));
    }

    /**
     * @return array
     */
    public function hasDataProvider()
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
        $url = null;
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $this->persistentCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn(null);

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
            ->method('fetch')
            ->with($keyLocalization)
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
            ->method('delete')
            ->withConsecutive(
                [$keyUrlLocalization],
                [$keySlugLocalization]
            );

        $this->urlCache->removeUrl($routeName, $routeParameters, $localization);
    }

    public function testDeleteAllPersistentClearable()
    {
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $persistentCache */
        $persistentCache = $this->createMock(CacheAllCapabilities::class);

        $this->localCache->expects($this->once())
            ->method('clear');
        $persistentCache->expects($this->once())
            ->method('deleteAll');

        $urlCache = new UrlKeyValueCache($persistentCache, $this->localCache);
        $urlCache->deleteAll();
    }

    public function testDeleteAllPersistentFileNotClearable()
    {
        $this->localCache->expects($this->once())
            ->method('clear');
        $this->persistentCache->expects($this->never())
            ->method($this->anything());

        $this->urlCache->deleteAll();
    }

    public function testFlushAllMulti()
    {
        $localCache = new ArrayAdapter();
        $persistentCache = $this->createMock(CacheProvider::class);

        $urlCache = new UrlKeyValueCache($persistentCache, $localCache);
        $urlCache->setUrl('test_1', null, '/test', 'test', 1);
        $urlCache->setUrl('test', ['id' => 1], '/my-test', null);

        $persistentCache->expects($this->exactly(2))
            ->method('saveMultiple')
            ->withConsecutive(
                [
                    [
                        'test_1_Tjs=_1_u' => '/test',
                        'test_1_Tjs=_1_s' => 'test',
                        'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u' => '/my-test'
                    ]
                ],
                [
                    [
                        'test_1_Tjs=_2_u' => '/test2',
                        'test_1_Tjs=_2_s' => 'test2',
                    ]
                ]
            );

        $this->assertNotEmpty($localCache->getValues());
        $urlCache->flushAll();
        $this->assertEmpty($localCache->getValues());

        // Check that second call of flushAll is not dependent on data from the first call
        $urlCache->setUrl('test_1', null, '/test2', 'test2', 2);
        $urlCache->flushAll();
    }

    public function testFlushAllMultiNoChanges()
    {
        $persistentCache = $this->createMock(CacheProvider::class);

        $urlCache = new UrlKeyValueCache($persistentCache, $this->localCache);

        $this->localCache->expects($this->once())
            ->method('getItems')
            ->with([])
            ->willReturn([]);
        $persistentCache->expects($this->never())
            ->method('saveMultiple');

        $urlCache->flushAll();
    }

    public function testFlushAllNonMulti()
    {
        $localCache = new ArrayAdapter();
        $urlCache = new UrlKeyValueCache($this->persistentCache, $localCache);
        $urlCache->setUrl('test_1', null, '/test', 'test', 1);
        $urlCache->setUrl('test', ['id' => 1], '/my-test', null);

        $this->persistentCache->expects($this->exactly(3))
            ->method('save')
            ->withConsecutive(
                ['test_1_Tjs=_1_u', '/test'],
                ['test_1_Tjs=_1_s', 'test'],
                ['test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u', '/my-test']
            );

        $urlCache->flushAll();
    }
}
