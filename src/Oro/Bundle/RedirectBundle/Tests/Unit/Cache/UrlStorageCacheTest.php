<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Cache;

use Oro\Bundle\CacheBundle\Provider\PhpFileCache;
use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UrlStorageCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PhpFileCache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $persistentCache;

    /**
     * @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localCache;

    /**
     * @var Filesystem|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filesystem;

    /**
     * @var UrlStorageCache
     */
    private $storageCache;

    protected function setUp(): void
    {
        $this->persistentCache = $this->createMock(PhpFileCache::class);
        $this->localCache = $this->createMock(CacheItemPoolInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->storageCache = new UrlStorageCache($this->persistentCache, $this->localCache, $this->filesystem, 1);
    }

    public function testDeleteAll()
    {
        $this->localCache->expects($this->once())
            ->method('clear');

        $this->persistentCache->expects($this->once())
            ->method('getDirectory')
            ->willReturn('/a');

        $this->filesystem->expects($this->once())
            ->method('remove')
            ->with('/a');

        $this->storageCache->deleteAll();
    }

    public function testDeleteAllWithNonFsPersistent()
    {
        $this->localCache->expects($this->once())
            ->method('clear');

        /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $persistentCache */
        $persistentCache = $this->createMock(CacheItemPoolInterface::class);
        $persistentCache->expects($this->once())
            ->method('clear');
        $storageCache = new UrlStorageCache($persistentCache, $this->localCache, $this->filesystem);
        $storageCache->deleteAll();
    }

    public function testHasInLocalCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $this->localCache->expects($this->once())
            ->method('hasItem')
            ->with($key)
            ->willReturn(true);
        $this->persistentCache->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->storageCache->has($routeName, $routeParameters));
    }

    public function testHasInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $this->localCache->expects($this->once())
            ->method('hasItem')
            ->with($key)
            ->willReturn(false);

        $this->persistentCache->expects($this->once())
            ->method('hasItem')
            ->with($key)
            ->willReturn(true);

        $this->assertTrue($this->storageCache->has($routeName, $routeParameters));
    }

    public function testGetUrlDataStorageCreateNew()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';

        $localItem = $this->assertLocalCacheNotContainsValue($key);
        $localItem->expects($this->once())
            ->method('set')
            ->with($this->isInstanceOf(UrlDataStorage::class));
        $localItem->expects($this->once())
            ->method('get')
            ->willReturn(new UrlDataStorage());

        $persistenceItem = $this->createMock(CacheItemInterface::class);
        $persistenceItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $this->persistentCache->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($persistenceItem);

        $this->assertFalse($this->storageCache->getUrl($routeName, $routeParameters));
    }

    public function testGetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';
        $localizationId = 1;

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->createMock(UrlDataStorage::class);
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('getUrl')
            ->with($routeParameters, $localizationId)
            ->willReturn($url);

        $this->assertEquals($url, $this->storageCache->getUrl($routeName, $routeParameters, $localizationId));
    }

    public function testRemoveUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $localizationId = 1;

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->createMock(UrlDataStorage::class);
        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $persistentStorage */
        $persistentStorage = $this->createMock(UrlDataStorage::class);
        $this->configureLocalCacheWithValue($key, $storage);

        $persistentItem = $this->createMock(CacheItemInterface::class);
        $persistentItem->expects($this->any())
            ->method('get')
            ->willReturn($persistentStorage);
        $persistentItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $this->persistentCache->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($persistentItem);

        $storage->expects($this->once())
            ->method('removeUrl')
            ->with($routeParameters, $localizationId);

        $this->storageCache->removeUrl($routeName, $routeParameters, $localizationId);
    }

    public function testGetSlug()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $slug = 'test';
        $localizationId = 1;

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->createMock(UrlDataStorage::class);
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('getSlug')
            ->with($routeParameters, $localizationId)
            ->willReturn($slug);

        $this->assertEquals($slug, $this->storageCache->getSlug($routeName, $routeParameters, $localizationId));
    }

    public function testSetUrl()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';
        $localizationId = 1;

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->createMock(UrlDataStorage::class);
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('setUrl')
            ->with($routeParameters, $url, $localizationId);

        $this->storageCache->setUrl($routeName, $routeParameters, $url, $localizationId);
    }

    public function testFlushExistsInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->createMock(UrlDataStorage::class);
        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $oldStorage */
        $oldStorage = $this->createMock(UrlDataStorage::class);
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('setUrl')
            ->with($routeParameters, $url);

        $this->storageCache->setUrl($routeName, $routeParameters, $url);

        $persistentItem = $this->createMock(CacheItemInterface::class);
        $persistentItem->expects($this->any())
            ->method('get')
            ->willReturn($oldStorage);
        $persistentItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $this->persistentCache->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($persistentItem);

        $oldStorage->expects($this->once())
            ->method('merge')
            ->with($storage);

        $this->persistentCache->expects($this->once())
            ->method('save')
            ->with($persistentItem);

        $this->storageCache->flushAll();
    }

    public function testFlushNotInPersistentCache()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $key = 'test_2';
        $url = '/test';

        /** @var UrlDataStorage|\PHPUnit\Framework\MockObject\MockObject $storage */
        $storage = $this->createMock(UrlDataStorage::class);
        $this->configureLocalCacheWithValue($key, $storage);

        $storage->expects($this->once())
            ->method('setUrl')
            ->with($routeParameters, $url);

        $this->storageCache->setUrl($routeName, $routeParameters, $url);

        $persistentItem = $this->createMock(CacheItemInterface::class);
        $persistentItem->expects($this->never())
            ->method('get');
        $persistentItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $persistentItem->expects($this->once())
            ->method('set')
            ->with($storage);

        $this->persistentCache->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($persistentItem);

        $this->persistentCache->expects($this->once())
            ->method('save')
            ->with($persistentItem);

        $this->storageCache->flushAll();
    }

    private function configureLocalCacheWithValue(string $key, UrlDataStorage $storage)
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->any())
            ->method('get')
            ->willReturn($storage);
        $item->expects($this->atLeastOnce())
            ->method('isHit')
            ->willReturn(true);

        $this->localCache->expects($this->atLeastOnce())
            ->method('getItem')
            ->with($key)
            ->willReturn($item);

        $this->localCache->expects($this->never())
            ->method('save');

        return $item;
    }

    /**
     * @param string $key
     * @return \PHPUnit\Framework\MockObject\MockObject|CacheItemInterface
     */
    private function assertLocalCacheNotContainsValue(string $key)
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $this->localCache->expects($this->once())
            ->method('getItem')
            ->with($key)
            ->willReturn($item);

        $this->localCache->expects($this->once())
            ->method('save')
            ->with($item);

        return $item;
    }
}
