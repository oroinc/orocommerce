<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Oro\Bundle\RedirectBundle\Cache\UrlKeyValueCache;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\CacheAllCapabilities;
use Symfony\Component\Filesystem\Filesystem;

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
     * @var Cache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localCache;

    /**
     * @var Filesystem|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filesystem;

    /**
     * @var UrlKeyValueCache
     */
    private $urlCache;

    protected function setUp(): void
    {
        $this->persistentCache = $this->createMock(Cache::class);
        $this->localCache = $this->createMock(Cache::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->urlCache = new UrlKeyValueCache($this->persistentCache, $this->localCache, $this->filesystem);
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
            ->method('contains')
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

        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn($url);

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

        $this->localCache->expects($this->once())
            ->method('save')
            ->with($keyLocalization, null);

        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn(false);

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

        $this->localCache->expects($this->never())
            ->method('save');

        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn(false);

        $this->assertFalse($this->urlCache->getUrl($routeName, $routeParameters, $localization));
    }

    public function testGetSlug()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $slug = 'test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_s';

        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn($slug);

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

        $this->localCache->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [$keyUrlLocalization, $url],
                [$keySlugLocalization, $slug]
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

        $this->localCache->expects($this->once())
            ->method('save')
            ->with($keyUrlLocalization, $url);

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
            ->method('delete')
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

    public function testDeleteAllLocalClearablePersistentClearable()
    {
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $localCache */
        $localCache = $this->createMock(CacheAllCapabilities::class);
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $persistentCache */
        $persistentCache = $this->createMock(CacheAllCapabilities::class);

        $localCache->expects($this->once())
            ->method('deleteAll');
        $persistentCache->expects($this->once())
            ->method('deleteAll');

        $urlCache = new UrlKeyValueCache($persistentCache, $localCache, $this->filesystem);
        $urlCache->deleteAll();
    }

    public function testDeleteAllLocalNotClearablePersistentFileNotClearable()
    {
        $this->localCache->expects($this->never())
            ->method($this->anything());
        $this->persistentCache->expects($this->never())
            ->method($this->anything());

        $this->urlCache->deleteAll();
    }

    public function testDeleteAllLocalClearablePersistentFilesystem()
    {
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $localCache */
        $localCache = $this->createMock(CacheAllCapabilities::class);
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $persistentCache */
        $persistentCache = $this->createMock(FilesystemCache::class);

        $localCache->expects($this->once())
            ->method('deleteAll');
        $persistentCache->expects($this->once())
            ->method('getDirectory')
            ->willReturn('/cache');
        $persistentCache->expects($this->once())
            ->method('getNamespace')
            ->willReturn('persistent');
        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with('/cache/persistent');

        $urlCache = new UrlKeyValueCache($persistentCache, $localCache, $this->filesystem);
        $urlCache->deleteAll();
    }

    public function testFlushAllMulti()
    {
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $localCache */
        $localCache = $this->createMock(CacheAllCapabilities::class);
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $persistentCache */
        $persistentCache = $this->createMock(FilesystemCache::class);

        $urlCache = new UrlKeyValueCache($persistentCache, $localCache, $this->filesystem);
        $urlCache->setUrl('test_1', null, '/test', 'test', 1);
        $urlCache->setUrl('test', ['id' => 1], '/my-test', null);

        $localCache->expects($this->once())
            ->method('fetchMultiple')
            ->with([
                'test_1_Tjs=_1_u',
                'test_1_Tjs=_1_s',
                'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u'
            ])
            ->willReturn(
                [
                    'test_1_Tjs=_1_u' => '/test',
                    'test_1_Tjs=_1_s' => 'test',
                    'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u' => '/my-test'
                ]
            );
        $persistentCache->expects($this->once())
            ->method('saveMultiple')
            ->with(
                [
                    'test_1_Tjs=_1_u' => '/test',
                    'test_1_Tjs=_1_s' => 'test',
                    'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u' => '/my-test'
                ]
            );

        $urlCache->flushAll();
    }

    public function testFlushAllMultiNoChanges()
    {
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $localCache */
        $localCache = $this->createMock(CacheAllCapabilities::class);
        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $persistentCache */
        $persistentCache = $this->createMock(FilesystemCache::class);

        $urlCache = new UrlKeyValueCache($persistentCache, $localCache, $this->filesystem);

        $localCache->expects($this->once())
            ->method('fetchMultiple')
            ->with([])
            ->willReturn([]);
        $persistentCache->expects($this->never())
            ->method('saveMultiple');

        $urlCache->flushAll();
    }

    public function testFlushAllNonMulti()
    {
        $this->urlCache->setUrl('test_1', null, '/test', 'test', 1);
        $this->urlCache->setUrl('test', ['id' => 1], '/my-test', null);

        $this->localCache->expects($this->exactly(3))
            ->method('fetch')
            ->withConsecutive(
                ['test_1_Tjs=_1_u'],
                ['test_1_Tjs=_1_s'],
                ['test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u']
            )
            ->willReturnOnConsecutiveCalls(
                '/test',
                'test',
                '/my-test'
            );
        $this->persistentCache->expects($this->exactly(3))
            ->method('save')
            ->withConsecutive(
                ['test_1_Tjs=_1_u', '/test'],
                ['test_1_Tjs=_1_s', 'test'],
                ['test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u', '/my-test']
            );

        $this->urlCache->flushAll();
    }
}
