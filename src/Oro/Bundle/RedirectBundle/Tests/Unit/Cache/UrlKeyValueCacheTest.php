<?php

namespace Oro\Bundle\RedirectBundle\Tests\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Oro\Bundle\RedirectBundle\Cache\UrlKeyValueCache;
use Oro\Bundle\RedirectBundle\Tests\Unit\Stub\CacheAllCapabilities;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UrlKeyValueCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistentCache;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localCache;

    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;

    /**
     * @var UrlKeyValueCache
     */
    private $urlCache;

    protected function setUp()
    {
        $this->persistentCache = $this->createMock(Cache::class);
        $this->localCache = $this->createMock(Cache::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->urlCache = new UrlKeyValueCache($this->persistentCache, $this->localCache, $this->filesystem);
    }

    public function testHasNoForNonDefaultLocalization()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';
        $keyDefault = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u';

        $this->localCache->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive(
                [$keyLocalization],
                [$keyDefault]
            )
            ->willReturn(false);

        $this->localCache->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [$keyDefault],
                [$keyLocalization]
            )
            ->willReturn(false);
        $this->localCache->expects($this->never())
            ->method('save');

        $this->persistentCache->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [$keyLocalization],
                [$keyDefault]
            )
            ->willReturn(false);

        $this->assertFalse($this->urlCache->has($routeName, $routeParameters, $localization));
    }

    public function testHasInLocalCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $this->localCache->expects($this->once())
            ->method('contains')
            ->with($keyLocalization)
            ->willReturn(true);

        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn($url);

        $this->persistentCache->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->urlCache->has($routeName, $routeParameters, $localization));
    }

    public function testHasInLocalCacheForDefaultLocalization()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';
        $keyDefault = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u';

        $this->localCache->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive(
                [$keyLocalization],
                [$keyDefault]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $this->localCache->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [$keyDefault],
                [$keyLocalization]
            )
            ->willReturn($url);
        $this->localCache->expects($this->once())
            ->method('save')
            ->with($keyLocalization, $url);

        $this->persistentCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn(false);

        $this->assertTrue($this->urlCache->has($routeName, $routeParameters, $localization));
    }

    public function testHasInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';
        $keyDefault = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u';

        $this->localCache->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive(
                [$keyLocalization],
                [$keyDefault]
            )
            ->willReturn(false);

        $this->localCache->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [$keyDefault],
                [$keyLocalization]
            )
            ->willReturn($url);
        $this->localCache->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [$keyDefault, $url],
                [$keyLocalization, $url]
            );

        $this->persistentCache->expects($this->exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [$keyLocalization],
                [$keyDefault]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                $url
            );

        $this->assertTrue($this->urlCache->has($routeName, $routeParameters, $localization));
    }

    public function testHasInPersistentCacheForDefaultLocalization()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $this->localCache->expects($this->once())
            ->method('contains')
            ->with($keyLocalization)
            ->willReturn(false);

        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn($url);

        $this->localCache->expects($this->once())
            ->method('save')
            ->with($keyLocalization, $url);

        $this->persistentCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn($url);

        $this->assertTrue($this->urlCache->has($routeName, $routeParameters, $localization));
    }

    public function testHasNoForDefaultLocalization()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = UrlKeyValueCache::DEFAULT_LOCALIZATION_ID;
        $keyDefault = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_0_u';

        $this->localCache->expects($this->once())
            ->method('contains')
            ->with($keyDefault)
            ->willReturn(false);

        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($keyDefault)
            ->willReturn(false);
        $this->localCache->expects($this->never())
            ->method('save');

        $this->persistentCache->expects($this->once())
            ->method('fetch')
            ->with($keyDefault)
            ->willReturn(false);

        $this->assertFalse($this->urlCache->has($routeName, $routeParameters, $localization));
    }

    public function testGetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $url = '/test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_u';

        $this->localCache->expects($this->once())
            ->method('contains')
            ->with($keyLocalization)
            ->willReturn(true);

        $this->localCache->expects($this->once())
            ->method('fetch')
            ->with($keyLocalization)
            ->willReturn($url);

        $this->assertSame($url, $this->urlCache->getUrl($routeName, $routeParameters, $localization));
    }

    public function testGetSlug()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $localization = 1;
        $slug = 'test';
        $keyLocalization = 'test_YToxOntzOjI6ImlkIjtpOjE7fQ==_1_s';

        $this->localCache->expects($this->once())
            ->method('contains')
            ->with($keyLocalization)
            ->willReturn(true);

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
        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $localCache */
        $localCache = $this->createMock(CacheAllCapabilities::class);
        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $persistentCache */
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
        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $localCache */
        $localCache = $this->createMock(CacheAllCapabilities::class);
        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $persistentCache */
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
        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $localCache */
        $localCache = $this->createMock(CacheAllCapabilities::class);
        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $persistentCache */
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
